<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Support\Api\Controllers;

use Hubzero\Component\ApiController;
use Hubzero\Utility\Date;
use Component;
use Exception;
use stdClass;
use Request;
use Config;
use Route;
use Lang;
use User;

require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'ticket.php');
require_once(dirname(dirname(__DIR__)) . DS . 'helpers' . DS . 'acl.php');

/**
 * API controller class for support tickets
 */
class Ticketsv1_0 extends ApiController
{
	/**
	 * Execute a request
	 *
	 * @return  void
	 */
	public function execute()
	{
		$this->config = Component::params('com_support');
		$this->database = \App::get('db');

		$this->acl = \Components\Support\Helpers\ACL::getACL();
		$this->acl->setUser(User::get('id'));

		parent::execute();
	}

	/**
	 * Displays ticket stats
	 *
	 * @apiMethod GET
	 * @apiUri    /support/stats
	 * @apiParameter {
	 * 		"name":          "type",
	 * 		"description":   "Ticket type",
	 * 		"type":          "string",
	 * 		"required":      false,
	 * 		"default":       "submitted"
	 * }
	 * @apiParameter {
	 * 		"name":          "group",
	 * 		"description":   "Group CN",
	 * 		"type":          "string",
	 * 		"required":      false,
	 * 		"default":       ""
	 * }
	 * @return    void
	 */
	public function statsTask()
	{
		$this->requiresAuthentication();

		if (!$this->acl->check('read', 'tickets'))
		{
			throw new Exception(Lang::txt('Not authorized'), 403);
		}

		$type = Request::getVar('type', 'submitted');
		$type = ($type == 'automatic') ? 1 : 0;

		$group = Request::getVar('group', '');

		// Set up some dates
		$this->offset = Config::get('offset');

		$year  = Request::getInt('year', strftime("%Y", time()+($this->offset*60*60)));
		$month = strftime("%m", time()+($this->offset*60*60));
		if ($month <= "9"&preg_match("#(^[1-9]{1})#",$month))
		{
			$month = "0$month";
		}
		$day   = strftime("%d", time()+($this->offset*60*60));
		if ($day <= "9"&preg_match("#(^[1-9]{1})#",$day))
		{
			$day = "0$day";
		}

		$stats = new stdClass;
		$stats->open = 0;
		$stats->new = 0;
		$stats->unassigned = 0;
		$stats->closed = 0;
		$stats->tickets = new stdClass;
		$stats->tickets->opened = array();
		$stats->tickets->closed = array();

		$st = new \Components\Support\Tables\Ticket($this->database);

		$sql = "SELECT id, created, YEAR(created) AS `year`, MONTH(created) AS `month`, status, owner
				FROM `#__support_tickets`
				WHERE report!=''
				AND type=" . $type . " AND open=1";
		if (!$group)
		{
			$sql .= " AND (`group`='' OR `group` IS NULL)";
		}
		else
		{
			$sql .= " AND `group`='{$group}'";
		}
		$sql .= " ORDER BY created ASC";
		$this->database->setQuery($sql);
		$openTickets = $this->database->loadObjectList();
		foreach ($openTickets as $o)
		{
			if (!isset($stats->tickets->opened[$o->year]))
			{
				$stats->tickets->opened[$o->year] = array();
			}
			if (!isset($stats->tickets->opened[$o->year][$o->month]))
			{
				$stats->tickets->opened[$o->year][$o->month] = 0;
			}
			$stats->tickets->opened[$o->year][$o->month]++;

			$stats->open++;

			if (!$o->status)
			{
				$stats->new++;
			}
			if (!$o->owner)
			{
				$stats->unassigned++;
			}
		}

		$this->send($stats);
	}

	/**
	 * Calculate time
	 *
	 * @param   string  $val  Timestamp or word [month, year, week, day]
	 * @return  string
	 */
	private function _toTimestamp($val=null)
	{
		if ($val)
		{
			$val = strtolower($val);

			if (strstr($val, ','))
			{
				$vals = explode(',', $val);
				foreach ($vals as $i => $v)
				{
					$vales[$i] = $this->_toTimestamp(trim($v));
				}
				return $vals;
			}
			switch ($val)
			{
				case 'year':
					$val = with(new Date(mktime(0, 0, 0, date("m"), date("d"), date("Y")-1)))->format("Y-m-d H:i:s");
				break;

				case 'month':
					$val = with(new Date(mktime(0, 0, 0, date("m")-1, date("d"), date("Y"))))->format("Y-m-d H:i:s");
				break;

				case 'week':
					$val = with(new Date(mktime(0, 0, 0, date("m"), date("d")-7, date("Y"))))->format("Y-m-d H:i:s");
				break;

				case 'day':
					$val = with(new Date(mktime(0, 0, 0, date("m"), date("d")-1, date("Y"))))->format("Y-m-d H:i:s");
				break;

				default:
					if (preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})[ ]([0-9]{2}):([0-9]{2}):([0-9]{2})/", $val, $regs))
					{
						// Time already matches pattern so do nothing.
						//$stime = mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
					}
					else if (preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})/", $val, $regs))
					{
						$val .= ' 00:00:00';
					}
					else if (preg_match("/([0-9]{4})-([0-9]{2})/", $val, $regs))
					{
						$val .= '-01 00:00:00';
					}
					else
					{
						// Not an acceptable time
					}
				break;
			}
		}

		return $val;
	}

	/**
	 * Display a list of tickets
	 *
	 * @apiMethod GET
	 * @apiUri    /support/list
	 * @apiParameter {
	 * 		"name":          "limit",
	 * 		"description":   "Number of result to return.",
	 * 		"type":          "integer",
	 * 		"required":      false,
	 * 		"default":       25
	 * }
	 * @apiParameter {
	 * 		"name":          "limitstart",
	 * 		"description":   "Number of where to start returning results.",
	 * 		"type":          "integer",
	 * 		"required":      false,
	 * 		"default":       0
	 * }
	 * @apiParameter {
	 * 		"name":          "search",
	 * 		"description":   "A word or phrase to search for.",
	 * 		"type":          "string",
	 * 		"required":      false,
	 * 		"default":       ""
	 * }
	 * @apiParameter {
	 * 		"name":          "sort",
	 * 		"description":   "Field to sort results by.",
	 * 		"type":          "string",
	 * 		"required":      false,
	 *      "default":       "created",
	 * 		"allowedValues": "created, id, state"
	 * }
	 * @apiParameter {
	 * 		"name":          "sort_Dir",
	 * 		"description":   "Direction to sort results by.",
	 * 		"type":          "string",
	 * 		"required":      false,
	 * 		"default":       "desc",
	 * 		"allowedValues": "asc, desc"
	 * }
	 * @return    void
	 */
	public function listTask()
	{
		$this->requiresAuthentication();

		if (!$this->acl->check('read', 'tickets'))
		{
			throw new Exception(Lang::txt('Not authorized'), 403);
		}

		$obj = new \Components\Support\Tables\Ticket($this->database);

		$filters = array(
			'limit'      => Request::getInt('limit', 25),
			'start'      => Request::getInt('limitstart', 0),
			'search'     => Request::getVar('search', ''),
			'sort'       => Request::getWord('sort', 'created'),
			'sortdir'    => strtoupper(Request::getWord('sort_Dir', 'DESC')),
			'group'      => Request::getVar('group', ''),
			'reportedby' => Request::getVar('reporter', ''),
			'owner'      => Request::getVar('owner', ''),
			'type'       => Request::getInt('type', 0),
			'status'     => strtolower(Request::getWord('status', '')),
			'tag'        => Request::getWord('tag', ''),
		);

		$filters['opened'] = $this->_toTimestamp(Request::getVar('opened', ''));
		$filters['closed'] = $this->_toTimestamp(Request::getVar('closed', ''));

		$response = new stdClass;
		$response->success = true;
		$response->total   = 0;
		$response->tickets = array();

		// Get a list of all statuses
		$sobj = new \Components\Support\Tables\Status($this->database);

		$statuses = array();
		if ($data = $sobj->find('all'))
		{
			foreach ($data as $status)
			{
				$statuses[$status->id] = $status;
			}
		}

		// Get a count of tickets
		$response->total = $obj->getTicketsCount($filters);

		if ($response->total)
		{
			$response->tickets = $obj->getTickets($filters);

			foreach ($response->tickets as $i => $ticket)
			{
				$owner = $ticket->owner;

				$response->tickets[$i]->owner = new stdClass;
				$response->tickets[$i]->owner->username = $owner;
				$response->tickets[$i]->owner->name     = $ticket->owner_name;
				$response->tickets[$i]->owner->id       = $ticket->owner_id;

				unset($response->tickets[$i]->owner_name);
				unset($response->tickets[$i]->owner_id);

				$response->tickets[$i]->reporter = new stdClass;
				$response->tickets[$i]->reporter->name     = $ticket->name;
				$response->tickets[$i]->reporter->username = $ticket->login;
				$response->tickets[$i]->reporter->email    = $ticket->email;

				unset($response->tickets[$i]->name);
				unset($response->tickets[$i]->login);
				unset($response->tickets[$i]->email);

				$status = $response->tickets[$i]->status;

				$response->tickets[$i]->status = new stdClass;
				if (!$status)
				{
					$response->tickets[$i]->status->alias = 'new';
					$response->tickets[$i]->status->title = 'New';
				}
				else
				{
					$response->tickets[$i]->status->alias = (isset($statuses[$status]) ? $statuses[$status]->alias : 'unknown');
					$response->tickets[$i]->status->title = (isset($statuses[$status]) ? $statuses[$status]->title : 'unknown');
				}
				$response->tickets[$i]->status->id    = $status;

				$response->tickets[$i]->url = str_replace('/api', '', rtrim(Request::base(), '/') . '/' . ltrim(Route::url('index.php?option=com_support&controller=tickets&task=tickets&id=' . $response->tickets[$i]->id), '/'));
			}
		}

		$this->send($response);
	}

	/**
	 * Create a new ticket
	 *
	 * @apiMethod POST
	 * @apiUri    /support
	 * @apiParameter {
	 * 		"name":        "scope",
	 * 		"description": "Scope type (group, member, etc.)",
	 * 		"type":        "string",
	 * 		"required":    true,
	 * 		"default":     null
	 * }
	 * @apiParameter {
	 * 		"name":        "scope_id",
	 * 		"description": "Scope object ID",
	 * 		"type":        "integer",
	 * 		"required":    true,
	 * 		"default":     null
	 * }
	 * @apiParameter {
	 * 		"name":        "title",
	 * 		"description": "Entry title",
	 * 		"type":        "string",
	 * 		"required":    true,
	 * 		"default":     null
	 * }
	 * @apiParameter {
	 * 		"name":        "alias",
	 * 		"description": "Entry alias",
	 * 		"type":        "string",
	 * 		"required":    false,
	 * 		"default":     null
	 * }
	 * @return     void
	 */
	public function createTask()
	{
		//get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		//make sure we have a user
		if ($result === false) return $this->not_found();

		// Initiate class and bind data to database fields
		$ticket = new \Components\Support\Models\Ticket();

		// Set the created date
		$ticket->set('created', Date::toSql());

		// Incoming
		$ticket->set('report', Request::getVar('report', '', 'post', 'none', 2));
		if (!$ticket->get('report'))
		{
			$this->errorMessage(500, Lang::txt('Error: Report contains no text.'));
			return;
		}
		$ticket->set('os', Request::getVar('os', 'unknown', 'post'));
		$ticket->set('browser', Request::getVar('browser', 'unknown', 'post'));
		$ticket->set('severity', Request::getVar('severity', 'normal', 'post'));

		// Cut suggestion at 70 characters
		$summary = substr($ticket->get('report'), 0, 70);
		if (strlen($summary) >= 70)
		{
			$summary .= '...';
		}
		$ticket->set('summary', $summary);

		// Get user data
		$ticket->set('name', $result->get('name'));
		$ticket->set('email', $result->get('email'));
		$ticket->set('login', $result->get('username'));

		// Set some helpful info
		$ticket->set('instances', 1);
		$ticket->set('section', 1);
		$ticket->set('open', 1);
		$ticket->set('status', 0);

		$ticket->set('ip', Request::ip());
		$ticket->set('hostname', gethostbyaddr(Request::getVar('REMOTE_ADDR','','server')));

		// Save the data
		if (!$ticket->store())
		{
			$this->errorMessage(500, $ticket->getErrors());
			return;
		}

		// Any tags?
		if ($tags = trim(Request::getVar('tags', '', 'post')))
		{
			$ticket->tag($tags, $result->get('uidNumber'));
		}

		// Set the response
		$msg = new stdClass;
		$msg->submitted = $ticket->get('created');
		$msg->ticket    = $ticket->get('id');

		$this->setMessageType(Request::getVar('format', 'json'));
		$this->send($msg, 200, 'OK');
	}

	/**
	 * Displays details for a ticket
	 *
	 * @apiMethod GET
	 * @apiUri    /support/{ticket}
	 * @apiParameter {
	 * 		"name":        "ticket",
	 * 		"description": "Ticket identifier",
	 * 		"type":        "integer",
	 * 		"required":    true,
	 * 		"default":     0
	 * }
	 * @return    void
	 */
	public function readTask()
	{
		$this->requiresAuthentication();

		if (!$this->acl->check('read', 'tickets'))
		{
			throw new Exception(Lang::txt('Not authorized'), 403);
		}

		// Initiate class and bind data to database fields
		$ticket_id = Request::getInt('ticket', 0);

		// Initiate class and bind data to database fields
		$ticket = new \Components\Support\Models\Ticket($ticket_id);

		$response = new stdClass;
		$response->id = $ticket->get('id');

		$response->owner = new stdClass;
		$response->owner->username = $ticket->owner('username');
		$response->owner->name     = $ticket->owner('name');
		$response->owner->id       = $ticket->owner('id');

		$response->reporter = new stdClass;
		$response->reporter->name     = $ticket->submitter('name');
		$response->reporter->username = $ticket->submitter('username');
		$response->reporter->email    = $ticket->submitter('email');

		$response->status = new stdClass;
		$response->status->alias = $ticket->status('class');
		$response->status->title = $ticket->status('text');
		$response->status->id    = $ticket->get('status');

		foreach (array('created', 'severity', 'os', 'browser', 'ip', 'hostname', 'uas', 'referrer', 'open', 'closed') as $prop)
		{
			$response->$prop = $ticket->get($prop);
		}

		$response->report = $ticket->content('raw');

		$response->url = str_replace('/api', '', rtrim(Request::base(), '/') . '/' . ltrim(Route::url('index.php?option=com_support&controller=tickets&task=tickets&id=' . $response->id), '/'));

		$response->comments = array();
		foreach ($ticket->comments() as $comment)
		{
			$c = new stdClass;
			$c->id = $comment->get('id');
			$c->created = $comment->get('created');
			$c->creator = new stdClass;
			$c->creator->username = $comment->creator('username');
			$c->creator->name     = $comment->creator('name');
			$c->creator->id       = $comment->creator('id');
			$c->private = ($comment->access ? true : false);
			$c->content = $comment->content('raw');

			$response->comments[] = $c;
		}

		$this->send($response);
	}

	/**
	 * Update a ticket
	 *
	 * @apiMethod PUT
	 * @apiUri    /support/{ticket}
	 * @apiParameter {
	 * 		"name":        "ticket",
	 * 		"description": "Ticket identifier",
	 * 		"type":        "integer",
	 * 		"required":    true,
	 * 		"default":     0
	 * }
	 * @return    void
	 */
	public function updateTask()
	{
		$this->requiresAuthentication();

		if (!$this->acl->check('edit', 'tickets'))
		{
			throw new Exception(Lang::txt('Not authorized'), 403);
		}

		// Initiate class and bind data to database fields
		$ticket_id = Request::getInt('ticket', 0);

		// Initiate class and bind data to database fields
		$row = new \Components\Support\Models\Ticket($ticket_id);

		if (!$row->exists())
		{
			throw new Exception(Lang::txt('COM_SUPPORT_ERROR_MISSING_RECORD'), 404);
		}

		$this->send(null, 204);
	}

	/**
	 * Delete a ticket
	 *
	 * @apiMethod DELETE
	 * @apiUri    /support/{ticket}
	 * @apiParameter {
	 * 		"name":        "ticket",
	 * 		"description": "Ticket identifier",
	 * 		"type":        "integer",
	 * 		"required":    true,
	 * 		"default":     0
	 * }
	 * @return    void
	 */
	public function deleteTask()
	{
		$this->requiresAuthentication();

		if (!$this->acl->check('delete', 'tickets'))
		{
			throw new Exception(Lang::txt('Not authorized'), 403);
		}

		// Initiate class and bind data to database fields
		$ticket_id = Request::getInt('ticket', 0);

		// Initiate class and bind data to database fields
		$row = new \Components\Support\Models\Ticket($ticket_id);

		if (!$row->exists())
		{
			throw new Exception(Lang::txt('COM_SUPPORT_ERROR_MISSING_RECORD'), 404);
		}

		if (!$row->delete())
		{
			throw new Exception($row->getError(), 500);
		}

		$this->send(null, 204);
	}
}
