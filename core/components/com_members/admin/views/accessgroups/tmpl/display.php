<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

$canDo = Components\Members\Helpers\Admin::getActions('component');

Toolbar::title(Lang::txt('COM_MEMBERS') . ': ' . Lang::txt('COM_MEMBERS_ACCESSGROUPS'), 'user');
if ($canDo->get('core.create'))
{
	Toolbar::addNew();
}
if ($canDo->get('core.edit'))
{
	Toolbar::editList();
	Toolbar::divider();
}
if ($canDo->get('core.delete'))
{
	Toolbar::deleteList('COM_MEMBERS_CONFIRM_DELETE');
	Toolbar::divider();
}
if ($canDo->get('core.admin'))
{
	Toolbar::preferences('com_members');
	Toolbar::divider();
}
Toolbar::help('groups');

// Load the tooltip behavior.
Html::behavior('tooltip');
Html::behavior('multiselect');
?>
<nav role="navigation" class="sub sub-navigation">
	<ul>
		<li>
			<a<?php if ($this->controller == 'accessgroups') { echo ' class="active"'; } ?> href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=accessgroups'); ?>"><?php echo Lang::txt('COM_MEMBERS_ACCESSGROUPS'); ?></a>
		</li>
		<li>
			<a<?php if ($this->controller == 'accesslevels') { echo ' class="active"'; } ?> href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=accesslevels'); ?>"><?php echo Lang::txt('COM_MEMBERS_ACCESSLEVELS'); ?></a>
		</li>
	</ul>
</nav>

<form action="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<div class="filter-search fltlft">
			<label class="filter-search-lbl" for="filter_search"><?php echo Lang::txt('COM_MEMBERS_SEARCH_GROUPS_LABEL'); ?></label>
			<input type="text" name="filter_search" id="filter_search" class="filter" value="<?php echo $this->escape($this->filters['search']); ?>" placeholder="<?php echo Lang::txt('COM_MEMBERS_SEARCH_IN_GROUPS'); ?>" />
			<button type="submit"><?php echo Lang::txt('JSEARCH_FILTER_SUBMIT'); ?></button>
			<button type="button" class="filter-clear"><?php echo Lang::txt('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
	</fieldset>

	<table class="adminlist">
		<thead>
			<tr>
				<th>
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Lang::txt('JGLOBAL_CHECK_ALL'); ?>" class="checkbox-toggle toggle-all" />
				</th>
				<th class="priority-4">
					<?php echo Lang::txt('JGRID_HEADING_ID'); ?>
				</th>
				<th class="left">
					<?php echo Lang::txt('COM_MEMBERS_HEADING_GROUP_TITLE'); ?>
				</th>
				<th class="priority-3">
					<?php echo Lang::txt('COM_MEMBERS_HEADING_USERS_IN_GROUP'); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="4">
					<?php echo $this->rows->pagination; ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->rows as $i => $row) :
			$canCreate = User::authorise('core.create', $this->option);
			$canEdit   = User::authorise('core.edit', $this->option);
			// If this group is super admin and this user is not super admin, $canEdit is false
			if (!User::authorise('core.admin') && Hubzero\Access\Access::checkGroup($row->get('id'), 'core.admin'))
			{
				$canEdit = false;
			}
			$canChange = User::authorise('core.edit.state', $this->option);

			$level = Hubzero\Access\Group::all()
				->where('lft', '<', $row->get('lft'))
				->where('rgt', '>', $row->get('rgt'))
				->total();
			?>
			<tr class="row<?php echo $i % 2; ?>">
				<td class="center">
					<?php if ($canEdit) : ?>
						<?php echo Html::grid('id', $i, $row->get('id')); ?>
					<?php endif; ?>
				</td>
				<td class="center priority-4">
					<?php echo (int) $row->id; ?>
				</td>
				<td>
					<?php echo str_repeat('<span class="gi">|&mdash;</span>', $level) ?>
					<?php if ($canEdit) : ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $row->get('id')); ?>">
							<?php echo $this->escape($row->get('title')); ?>
						</a>
					<?php else : ?>
						<?php echo $this->escape($row->get('title')); ?>
					<?php endif; ?>
					<?php if (Config::get('debug')) : ?>
						<a class="button fltrt" href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=debug&id=' . (int) $row->get('id')); ?>">
							<?php echo Lang::txt('COM_MEMBERS_DEBUG_GROUP');?>
						</a>
					<?php endif; ?>
				</td>
				<td class="center priority-3">
					<?php echo $row->maps()->select('group_id', 'count', true)->rows(false)->first()->count; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->filters['sort']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->filters['sort_Dir']; ?>" />
	<?php echo Html::input('token'); ?>
</form>
