<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Components\Jobs\Site;

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

		if (!empty($query['task']))
		{
			if ($query['task'] != 'all')
			{
				$segments[] = $query['task'];
			}
			unset($query['task']);
		}

		if (!empty($query['id']))
		{
			$segments[] = $query['id'];
			unset($query['id']);
		}

		if (!empty($query['code']))
		{
			$segments[] = $query['code'];
			unset($query['code']);
		}

		if (!empty($query['employer']))
		{
			$segments[] = $query['employer'];
			unset($query['employer']);
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

		// Count route segments
		$count = count($segments);

		if (empty($segments[0]))
		{
			// default to all jobs
			$vars['task'] = 'all';
			return $vars;
		}

		if (!intval($segments[0]) && empty($segments[1]))
		{
			// some general task
			$vars['task'] = $segments[0];
			return $vars;
		}

		if (!empty($segments[1]))
		{
			switch ($segments[0])
			{
				case 'job':
					$vars['task'] = 'job';
					$vars['code'] = $segments[1];
					return $vars;
				break;
				case 'editjob':
					$vars['task'] = 'editjob';
					$vars['code'] = $segments[1];
					return $vars;
				break;
				case 'editresume':
					$vars['task'] = 'editresume';
					$vars['id'] = $segments[1];
					return $vars;
				break;
				case 'apply':
					$vars['task'] = 'apply';
					$vars['code'] = $segments[1];
					return $vars;
				break;
				case 'editapp':
					$vars['task'] = 'editapp';
					$vars['code'] = $segments[1];
					return $vars;
				break;
				case 'withdraw':
					$vars['task'] = 'withdraw';
					$vars['code'] = $segments[1];
					return $vars;
				break;
				case 'confirmjob':
					$vars['task'] = 'confirmjob';
					$vars['code'] = $segments[1];
					return $vars;
				break;
				case 'unpublish':
					$vars['task'] = 'unpublish';
					$vars['code'] = $segments[1];
					return $vars;
				break;
				case 'reopen':
					$vars['task'] = 'reopen';
					$vars['code'] = $segments[1];
					return $vars;
				break;
				case 'remove':
					$vars['task'] = 'remove';
					$vars['code'] = $segments[1];
					return $vars;
				break;
				case 'browse':
				case 'all':
					$vars['task'] = 'browse';
					$vars['employer'] = $segments[1];
					return $vars;
				break;
			}
		}

		return $vars;
	}
}
