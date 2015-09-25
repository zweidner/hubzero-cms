<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace Components\Cron\Admin\Controllers;

use Components\Cron\Models\Job;
use Hubzero\Component\AdminController;
use stdClass;
use Request;
use Notify;
use Event;
use Route;
use Lang;
use User;
use Date;
use App;

/**
 * Cron controller class for jobs
 */
class Jobs extends AdminController
{
	/**
	 * Execute a task
	 *
	 * @return  void
	 */
	public function execute()
	{
		$this->registerTask('add', 'edit');
		$this->registerTask('apply', 'save');
		$this->registerTask('publish', 'state');
		$this->registerTask('unpublish', 'state');

		parent::execute();
	}

	/**
	 * Displays a form for editing an entry
	 *
	 * @return	void
	 */
	public function displayTask()
	{
		// Filters
		$this->view->filters = array(
			'sort' => trim(Request::getState(
				$this->_option . '.jobs.sort',
				'filter_order',
				'id'
			)),
			'sort_Dir' => trim(Request::getState(
				$this->_option . '.jobs.sortdir',
				'filter_order_Dir',
				'ASC'
			))
		);

		/*if ($this->view->filters['search'])
		{
			$record->whereLike('fullname', $this->view->filters['search']);
		}*/

		$record = Job::all();

		$this->view->rows = $record->ordered('filter_order', 'filter_order_Dir')->paginated();

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Displays a form for editing an entry
	 *
	 * @param   mixed  $row
	 * @return  void
	 */
	public function editTask($row=null)
	{
		Request::setVar('hidemainmenu', 1);

		// Load info from database
		if (!is_object($row))
		{
			// Incoming
			$id = Request::getVar('id', array(0));
			if (is_array($id))
			{
				$id = intval($id[0]);
			}

			$row = Job::oneOrNew($id);
		}

		if (!$row->get('id'))
		{
			$row->set('created', Date::toSql());
			$row->set('created_by', User::get('id'));
			$row->set('recurrence', '');
		}
		$row->set('minute', '*');
		$row->set('hour', '*');
		$row->set('day', '*');
		$row->set('month', '*');
		$row->set('dayofweek', '*');
		if ($row->get('recurrence'))
		{
			$bits = explode(' ', $row->get('recurrence'));

			$row->set('minute', $bits[0]);
			$row->set('hour', $bits[1]);
			$row->set('day', $bits[2]);
			$row->set('month', $bits[3]);
			$row->set('dayofweek', $bits[4]);
		}

		$defaults = array(
			'',
			'0 0 1 1 *',
			'0 0 1 * *',
			'0 0 * * 0',
			'0 0 * * *',
			'0 * * * *'
		);
		if (!in_array($row->get('recurrence'), $defaults))
		{
			$row->set('recurrence', 'custom');
		}

		$e = array();

		$events = Event::trigger('cron.onCronEvents');
		if ($events)
		{
			foreach ($events as $event)
			{
				$e[$event->plugin] = $event->events;
			}
		}

		$database = App::get('db');
		$database->setQuery("SELECT p.* FROM `#__extensions` AS p WHERE p.type='plugin' AND p.folder='cron' AND enabled=1 ORDER BY p.ordering");
		$this->view->plugins = $database->loadObjectList();
		if ($this->view->plugins)
		{
			foreach ($this->view->plugins as $key => $plugin)
			{
				$this->view->plugins[$key]->events = (isset($e[$plugin->element])) ? $e[$plugin->element] : array();
			}
		}

		$this->view->row = $row;

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view
			->setLayout('edit')
			->display();
	}

	/**
	 * Save changes to an entry
	 *
	 * @return  void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken();

		// Incoming
		$fields = Request::getVar('fields', array(), 'post');

		$recurrence = array();
		if (isset($fields['minute']))
		{
			$recurrence[] = ($fields['minute']['c']) ? $fields['minute']['c'] : $fields['minute']['s'];
		}
		if (isset($fields['hour']))
		{
			$recurrence[] = ($fields['hour']['c']) ? $fields['hour']['c'] : $fields['hour']['s'];
		}
		if (isset($fields['day']))
		{
			$recurrence[] = ($fields['day']['c']) ? $fields['day']['c'] : $fields['day']['s'];
		}
		if (isset($fields['month']))
		{
			$recurrence[] = ($fields['month']['c']) ? $fields['month']['c'] : $fields['month']['s'];
		}
		if (isset($fields['dayofweek']))
		{
			$recurrence[] = ($fields['dayofweek']['c']) ? $fields['dayofweek']['c'] : $fields['dayofweek']['s'];
		}
		if (!empty($recurrence))
		{
			$fields['recurrence'] = implode(' ', $recurrence);
		}
		unset($fields['minute']);
		unset($fields['hour']);
		unset($fields['day']);
		unset($fields['month']);
		unset($fields['dayofweek']);

		// Initiate extended database class
		$row = Job::oneOrNew($fields['id'])->set($fields);

		if ($row->get('recurrence'))
		{
			$row->set('next_run', $row->nextRun());
		}

		$p = new \Hubzero\Config\Registry(Request::getVar('params', '', 'post'));

		$row->set('params', $p->toString());

		// Store content
		if (!$row->save())
		{
			Notify::error($row->getError());
			return $this->editTask($row);
		}

		Notify::success(Lang::txt('COM_CRON_ITEM_SAVED'));

		if ($this->getTask() == 'apply')
		{
			return $this->editTask($row);
		}

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}

	/**
	 * Deletes one or more records and redirects to listing
	 *
	 * @return  void
	 */
	public function runTask()
	{
		// Check for request forgeries
		Request::checkToken();

		// Incoming
		$ids = Request::getVar('id', array());

		// Ensure we have an ID to work with
		if (empty($ids))
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				Lang::txt('COM_CRON_ERROR_NO_ITEMS_SELECTED'),
				'error'
			);
			return;
		}

		$output = new stdClass;
		$output->jobs = array();

		// Loop through each ID
		foreach ($ids as $id)
		{
			$job = Job::oneOrFail(intval($id));

			if (!$job->get('id') || $job->get('active'))
			{
				continue;
			}

			$job->mark('start_run');

			// Show related content
			$results = Event::trigger('cron.' . $job->get('event'), array($job));

			if ($results && is_array($results))
			{
				// Set it as active in case there were multiple plugins called on
				// the event. This is to ensure ALL processes finished.
				$job->set('active', 1);

				foreach ($results as $result)
				{
					if ($result)
					{
						$job->set('active', 0);
					}
				}
			}

			$job->mark('end_run');
			$job->set('last_run', Date::toLocal('Y-m-d H:i:s'));
			$job->set('next_run', $job->nextRun());
			$job->save();

			$output->jobs[] = $job->toArray();
		}

		$this->view->output = $output;

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Deletes one or more records and redirects to listing
	 *
	 * @return  void
	 */
	public function removeTask()
	{
		// Check for request forgeries
		Request::checkToken();

		// Incoming
		$ids = Request::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Ensure we have an ID to work with
		if (empty($ids))
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option, false),
				Lang::txt('COM_CRON_ERROR_NO_ITEMS_SELECTED'),
				'error'
			);
			return;
		}

		// Loop through each ID
		foreach ($ids as $id)
		{
			$row = Job::oneOrFail(intval($id));

			if (!$row->destroy())
			{
				Notify::error($row->getError());
			}
		}

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option, false),
			Lang::txt('COM_CRON_ITEMS_DELETED')
		);
	}

	/**
	 * Sets the state of one or more entries
	 *
	 * @param   integer  $state  The state to set entries to
	 * @return  void
	 */
	public function stateTask($state=0)
	{
		// Check for request forgeries
		Request::checkToken(['get', 'post']);

		// Incoming
		$state = $this->_task == 'publish' ? 1 : 0;

		$ids = Request::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Check for an ID
		if (count($ids) < 1)
		{
			$action = ($state == 1) ? Lang::txt('COM_CRON_STATE_UNPUBLISH') : Lang::txt('COM_CRON_STATE_PUBLISH');

			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				Lang::txt('COM_CRON_ERROR_SELECT_ITEMS', $action),
				'error'
			);
			return;
		}

		$total = 0;
		foreach ($ids as $id)
		{
			// Update record(s)
			$row = Job::oneOrFail(intval($id));
			$row->set('state', $state);
			if (!$row->save())
			{
				Notify::error($row->getError());
				continue;
			}

			$total++;
		}

		// Set message
		if ($state == 1)
		{
			Notify::success(Lang::txt('COM_CRON_ITEMS_PUBLISHED', $total));
		}
		else
		{
			Notify::success(Lang::txt('COM_CRON_ITEMS_UNPUBLISHED', $total));
		}

		App::redirect(
			Route::url('index.php?option=' . $this->_option, false)
		);
	}
}

