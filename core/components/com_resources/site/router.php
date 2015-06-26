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

namespace Components\Resources\Site;

use Hubzero\Component\Router\Base;

/**
 * Routing class for the component
 */
class Router extends Base
{
	/**
	 * Build the route for the component.
	 *
	 * @param   array  &$query  An array of URL arguments
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 */
	public function build(&$query)
	{
		$segments = array();

		if (!empty($query['task']) && in_array($query['task'], array('new', 'draft', 'start', 'retract', 'delete', 'discard', 'remove', 'reorder')))
		{
			if (!empty($query['task']))
			{
				if ($query['task'] == 'start')
				{
					$query['task'] = 'draft';
				}
				$segments[] = $query['task'];
				unset($query['task']);
			}
			if (!empty($query['id']))
			{
				$segments[] = $query['id'];
				unset($query['id']);
			}
		}
		else
		{
			if (!empty($query['id']))
			{
				$segments[] = $query['id'];
				unset($query['id']);
			}
			if (!empty($query['alias']))
			{
				$segments[] = $query['alias'];
				unset($query['alias']);
			}
			if (!empty($query['active']))
			{
				$segments[] = $query['active'];
				unset($query['active']);
			}
			if (!empty($query['task']))
			{
				$segments[] = $query['task'];
				unset($query['task']);
			}
			if (!empty($query['file']))
			{
				$segments[] = $query['file'];
				unset($query['file']);
			}
			if (!empty($query['type']))
			{
				$segments[] = $query['type'];
				unset($query['type']);
			}
		}

		return $segments;
	}

	/**
	 * Parse the segments of a URL.
	 *
	 * @param   array  &$segments  The segments of the URL to parse.
	 * @return  array  The URL attributes to be used by the application.
	 */
	public function parse(&$segments)
	{
		$vars = array();

		if (empty($segments[0]))
		{
			return $vars;
		}

		if (is_numeric($segments[0]))
		{
			$vars['id'] = $segments[0];
		}
		elseif (in_array($segments[0], array('browse', 'license', 'sourcecode')))
		{
			$vars['task'] = $segments[0];
		}
		elseif (in_array($segments[0], array('new', 'draft', 'start', 'retract', 'delete', 'discard', 'remove', 'reorder')))
		{
			$vars['task'] = $segments[0];
			$vars['controller'] = 'create';
			if (isset($segments[1]))
			{
				$vars['id'] = $segments[1];
			}
		}
		else
		{
			include_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'type.php');

			$database = \App::get('db');

			$t = new \Components\Resources\Tables\Type($database);
			$types = $t->getMajorTypes();

			// Normalize the title
			// This is so we can determine the type of resource to display from the URL
			// For example, /resources/learningmodules => Learning Modules
			for ($i = 0; $i < count($types); $i++)
			{
				//$normalized = preg_replace("/[^a-zA-Z0-9]/", '', $types[$i]->type);
				//$normalized = strtolower($normalized);

				if (trim($segments[0]) == $types[$i]->alias)
				{
					$vars['type'] = $segments[0];
					$vars['task'] = 'browsetags';
				}
			}

			if ($segments[0] == 'license')
			{
				$vars['task'] = $segments[0];
			}
			else
			{
				if (!isset($vars['type']))
				{
					$vars['alias'] = $segments[0];
				}
			}
		}

		if (!empty($segments[1]))
		{
			switch ($segments[1])
			{
				case 'download':
					$vars['task'] = 'download';
					if (isset($segments[2]))
					{
						$vars['file'] = $segments[2];
					}
				break;
				case 'play':     $vars['task'] = 'play';     break;
				case 'watch':	 $vars['task'] = 'watch';	 break;
				case 'video':	 $vars['task'] = 'video';	 break;
				//case 'license':  $vars['task'] = 'license';  break;
				case 'citation': $vars['task'] = 'citation'; break;
				case 'feed.rss': $vars['task'] = 'feed';     break;
				case 'feed':     $vars['task'] = 'feed';     break;

				case 'license':
				case 'sourcecode':
					$vars['tool'] = $segments[1];
				break;

				default:
					if ($segments[0] == 'browse')
					{
						$vars['type'] = $segments[1];
					}
					else
					{
						$vars['active'] = $segments[1];
					}
				break;
			}
		}

		return $vars;
	}
}
