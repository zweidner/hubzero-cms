<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Components\Storefront\Admin\Controllers;

use Hubzero\Component\AdminController;
use Components\Storefront\Models\Archive;
use Components\Storefront\Models\Warehouse;
use Components\Storefront\Models\OptionGroup;
use Request;
use Config;
use Route;
use Lang;
use App;

require_once dirname(dirname(__DIR__)) . DS . 'models' . DS . 'Warehouse.php';
require_once dirname(dirname(__DIR__)) . DS . 'models' . DS . 'OptionGroup.php';

/**
 * Controller class for knowledge base collections
 */
class Optiongroups extends AdminController
{
	/**
	 * Display a list of all option groups
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		$this->view->filters = array(
			// Get sorting variables
			'sort' => Request::getState(
				$this->_option . '.' . $this->_controller . '.sort',
				'filter_order',
				'title'
			),
			'sort_Dir' => Request::getState(
				$this->_option . '.' . $this->_controller . '.sortdir',
				'filter_order_Dir',
				'ASC'
			),
			// Get paging variables
			'limit' => Request::getState(
				$this->_option . '.' . $this->_controller . '.limit',
				'limit',
				Config::get('list_limit'),
				'int'
			),
			'start' => Request::getState(
				$this->_option . '.' . $this->_controller . '.limitstart',
				'limitstart',
				0,
				'int'
			)
		);

		$obj = new Archive();

		// Get record count
		$this->view->total = $obj->optionGroups('count', $this->view->filters);

		// Get records
		$this->view->rows = $obj->optionGroups('list', $this->view->filters);

		// For all records here get options
		$options = new \stdClass();
		$warehouse = new Warehouse();
		foreach ($this->view->rows as $r)
		{
			$key = $r->ogId;
			$allOptions = $warehouse->getOptionGroupOptions($key, 'rows', false);

			// Count how many active and how many inactive options there are
			$optionCounter = new \stdClass();
			$optionCounter->active = 0;
			$optionCounter->inactive = 0;
			foreach ($allOptions as $optionInfo)
			{
				if ($optionInfo->oActive)
				{
					$optionCounter->active++;
				}
				else
				{
					$optionCounter->inactive++;
				}
			}
			$options->$key = $optionCounter;
		}

		$this->view->options = $options;

		// Set any errors
		if ($this->getError())
		{
			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Create a new category
	 *
	 * @return  void
	 */
	public function addTask()
	{
		$this->editTask();
	}

	/**
	 * Edit a category
	 *
	 * @param   object  $row
	 * @return  void
	 */
	public function editTask($row=null)
	{
		Request::setVar('hidemainmenu', 1);

		$obj = new Archive();

		if (is_object($row))
		{
			$this->view->row = $row;
			$this->view->task = 'edit';
		}
		else
		{
			// Incoming
			$id = Request::getArray('ogId', array(0));

			if (is_array($id) && !empty($id))
			{
				$id = $id[0];
			}

			// Load option group
			$this->view->row = $obj->optionGroup($id);
		}

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
	 * Save a category and come back to the edit form
	 *
	 * @return  void
	 */
	public function applyTask()
	{
		$this->saveTask(false);
	}

	/**
	 * Save a product
	 *
	 * @param   boolean  $redirect  Redirect the page after saving
	 * @return  void
	 */
	public function saveTask($redirect=true)
	{
		// Check for request forgeries
		Request::checkToken();

		// Incoming
		$fields = Request::getArray('fields', array(), 'post');

		// Update record(s)
		$obj = new Archive();

		try
		{
			$optionGroup = $obj->updateOptionGroup($fields['ogId'], $fields);
		}
		catch (\Exception $e)
		{
			\Notify::error($e->getMessage());
			// Get the product
			$optionGroup = $obj->optionGroup($fields['ogId']);
			$this->editTask($optionGroup);
			return;
		}

		if ($redirect)
		{
			// Redirect
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				Lang::txt('COM_STOREFRONT_OPTION_GROUP_SAVED')
			);
			return;
		}
		Notify::success(Lang::txt('COM_STOREFRONT_OPTION_GROUP_SAVED'));

		$this->editTask($optionGroup);
	}

	/**
	 * Remove an entry
	 *
	 * @return  void
	 */
	public function removeTask()
	{
		// Incoming
		$step = Request::getInt('step', 1);
		$step = (!$step) ? 1 : $step;

		// What step are we on?
		switch ($step)
		{
			case 1:
				Request::setVar('hidemainmenu', 1);

				// Incoming
				$id = Request::getArray('id', array(0));
				if (!is_array($id) && !empty($id))
				{
					$id = array($id);
				}

				$this->view->ogId = $id;

				// Set any errors
				if ($this->getError())
				{
					$this->view->setError($this->getError());
				}

				// Output the HTML
				$this->view->display();
			break;

			case 2:
				// Check for request forgeries
				Request::checkToken();

				// Incoming
				$ogIds = Request::getInt('ogId', 0);

				// Make sure we have ID(s) to work with
				if (empty($ogIds))
				{
					App::redirect(
						Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=dispaly', false),
						Lang::txt('COM_STOREFRONT_NO_ID'),
						'error'
					);
					return;
				}

				$delete = Request::getInt('delete', 0);

				$msg = "Delete canceled";
				$type = 'error';
				if ($delete)
				{
					// Do the delete
					$obj = new Archive();
					$warnings = array();

					foreach ($ogIds as $ogId)
					{
						// Delete option group
						try
						{
							$optionGroup = new OptionGroup($ogId);
							$optionGroup->delete();

							// see if there are any warnings to display
							if ($optionGroupWarnings = $optionGroup->getMessages())
							{
								foreach ($optionGroupWarnings as $optionGroupWarning)
								{
									if (!in_array($optionGroupWarning, $warnings))
									{
										$warnings[] = $optionGroupWarning;
									}
								}
							}
						}
						catch (\Exception $e)
						{
							App::redirect(
								Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=dispaly', false),
								$e->getMessage(),
								$type
							);
							return;
						}
					}

					$msg = "Option group(s) deleted";
					$type = 'message';
				}

				// Set the redirect
				App::redirect(
					Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=dispaly', false),
					$msg,
					$type
				);
				if ($warnings)
				{
					foreach ($warnings as $warning)
					{
						\Notify::warning($warning);
					}
				}
			break;
		}
	}

	/**
	 * Calls stateTask to publish entries
	 *
	 * @return  void
	 */
	public function publishTask()
	{
		$this->stateTask(1);
	}

	/**
	 * Calls stateTask to unpublish entries
	 *
	 * @return  void
	 */
	public function unpublishTask()
	{
		$this->stateTask(0);
	}

	/**
	 * Set the state of an entry
	 *
	 * @param   integer  $state  State to set
	 * @return  void
	 */
	public function stateTask($state = 0)
	{
		$ids = Request::getArray('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Check for an ID
		if (count($ids) < 1)
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				($state == 1 ? Lang::txt('COM_STOREFRONT_SELECT_PUBLISH') : Lang::txt('COM_STOREFRONT_SELECT_UNPUBLISH')),
				'error'
			);
			return;
		}

		// Update record(s)
		$obj = new Archive();

		foreach ($ids as $ogId)
		{
			// Save category
			try
			{
				$obj->updateOptionGroup($ogId, array('state' => $state));
			}
			catch (\Exception $e)
			{
				\Notify::error($e->getMessage());
				return;
			}
		}

		// Set message
		switch ($state)
		{
			case '-1':
				$message = Lang::txt('COM_STOREFRONT_ARCHIVED', count($ids));
			break;
			case '1':
				$message = Lang::txt('COM_STOREFRONT_PUBLISHED', count($ids));
			break;
			case '0':
				$message = Lang::txt('COM_STOREFRONT_UNPUBLISHED', count($ids));
			break;
		}

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			$message
		);
	}

	/**
	 * Cancel a task (redirects to default task)
	 *
	 * @return  void
	 */
	public function cancelTask()
	{
		// Set the redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}
}
