<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

$canDo = \Components\Collections\Helpers\Permissions::getActions('collection');

$text = ($this->task == 'edit' ? Lang::txt('JACTION_EDIT') : Lang::txt('JACTION_CREATE'));

Toolbar::title(Lang::txt('COM_COLLECTIONS') . ': ' . $text, 'collection');
if ($canDo->get('core.edit'))
{
	Toolbar::apply();
	Toolbar::save();
	Toolbar::spacer();
}
Toolbar::cancel();
Toolbar::spacer();
Toolbar::help('collection');

Html::behavior('formvalidation');
Html::behavior('keepalive');

$this->js();
?>

<form action="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller); ?>" method="post" name="adminForm" class="editform form-validate" id="item-form" data-invalid-msg="<?php echo $this->escape(Lang::txt('JGLOBAL_VALIDATION_FORM_FAILED'));?>">
	<?php if ($this->getError()) { ?>
		<p class="error"><?php echo implode('<br />', $this->getErrors()); ?></p>
	<?php } ?>
	<div class="grid">
		<div class="col span7">
			<fieldset class="adminform">
				<legend><span><?php echo Lang::txt('JDETAILS'); ?></span></legend>

				<div class="grid">
					<div class="col span6">
						<div class="input-wrap">
							<label for="field-object_type"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_OWNER_TYPE'); ?>: <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label><br />
							<select name="fields[object_type]" id="field-object_type" class="required">
								<!-- <option value="site"<?php if ($this->row->get('object_type') == 'site' || $this->row->get('object_type') == '') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COLLECTIONS_FIELD_OWNER_TYPE_SITE'); ?></option> -->
								<option value="member"<?php if ($this->row->get('object_type') == 'member') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COLLECTIONS_FIELD_OWNER_TYPE_MEMBER'); ?></option>
								<option value="group"<?php if ($this->row->get('object_type') == 'group') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COLLECTIONS_FIELD_OWNER_TYPE_GROUP'); ?></option>
							</select>
						</div>
					</div>
					<div class="col span6">
						<div class="input-wrap" data-hint="<?php echo Lang::txt('COM_COLLECTIONS_FIELD_OWNER_ID_HINT'); ?>">
							<label for="field-object_id"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_OWNER_ID'); ?>: <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label>
							<input type="text" name="fields[object_id]" id="field-object_id" class="required" maxlength="250" value="<?php echo $this->escape(stripslashes($this->row->get('object_id'))); ?>" />
							<span class="hint"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_OWNER_ID_HINT'); ?></span>
						</div>
					</div>
				</div>

				<div class="input-wrap">
					<label for="field-title"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_TITLE'); ?>: <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label>
					<input type="text" name="fields[title]" id="field-title" class="required" maxlength="250" value="<?php echo $this->escape(stripslashes($this->row->get('title'))); ?>" />
				</div>

				<div class="input-wrap" data-hint="<?php echo Lang::txt('COM_COLLECTIONS_FIELD_ALIAS_HINT'); ?>">
					<label for="field-alias"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_ALIAS'); ?>:</label>
					<input type="text" name="fields[alias]" id="field-alias" size="30" maxlength="250" value="<?php echo $this->escape(stripslashes($this->row->get('alias'))); ?>" />
					<span class="hint"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_ALIAS_HINT'); ?></span>
				</div>

				<div class="input-wrap">
					<label for="field-description"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_DESCRIPTION'); ?></label>
					<?php echo $this->editor('fields[description]', $this->escape($this->row->get('description')), 35, 10, 'field-description', array('class' => 'required minimal no-footer', 'buttons' => false)); ?>
				</div>

				<div class="grid">
					<div class="col span6">
						<div class="input-wrap">
							<label for="field-layout"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_LAYOUT'); ?></label>
							<select name="fields[layout]" id="field-layout">
								<option value="grid"<?php if ($this->row->get('layout') == 'grid') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COLLECTIONS_FIELD_LAYOUT_GRID'); ?></option>
								<option value="list"<?php if ($this->row->get('layout') == 'list') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COLLECTIONS_FIELD_LAYOUT_LIST'); ?></option>
							</select>
						</div>
					</div>
					<div class="col span6">
						<div class="input-wrap">
							<label for="field-sort"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_SORT'); ?></label>
							<select name="fields[sort]" id="field-sort">
								<option value="created"<?php if ($this->row->get('sort') == 'created') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COLLECTIONS_FIELD_SORT_CREATED'); ?></option>
								<option value="ordering"<?php if ($this->row->get('sort') == 'ordering') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COLLECTIONS_FIELD_SORT_ORDERING'); ?></option>
							</select>
						</div>
					</div>
				</div>
			</fieldset>
		</div>
		<div class="col span5">
			<table class="meta">
				<tbody>
					<tr>
						<th class="key"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_CREATOR'); ?>:</th>
						<td>
							<?php
							$editor = User::getInstance($this->row->get('created_by'));
							echo $this->escape(stripslashes($editor->get('name')));
							?>
							<input type="hidden" name="fields[created_by]" id="field-created_by" value="<?php echo $this->escape($this->row->get('created_by')); ?>" />
						</td>
					</tr>
					<tr>
						<th class="key"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_CREATED'); ?>:</th>
						<td>
							<?php echo Date::of($this->row->get('created'))->toLocal(); ?>
							<input type="hidden" name="fields[created]" id="field-created" value="<?php echo $this->escape($this->row->get('created')); ?>" />
						</td>
					</tr>
					<tr>
						<th class="key"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_LIKES'); ?>:</th>
						<td>
							<?php echo $this->row->get('positive', 0); ?>
							<input type="hidden" name="fields[positive]" id="field-positive" value="<?php echo $this->escape($this->row->get('positive', 0)); ?>" />
						</td>
					</tr>
					<tr>
						<th class="key"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_POSTS'); ?>:</th>
						<td>
							<?php echo $this->row->posts()->total(); ?>
						</td>
					</tr>
					<tr>
						<th class="key"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_FOLLOWERS'); ?>:</th>
						<td>
							<?php //echo $this->row->count('followers'); ?>
						</td>
					</tr>
				</tbody>
			</table>

			<fieldset class="adminform">
				<legend><span><?php echo Lang::txt('JGLOBAL_FIELDSET_PUBLISHING'); ?></span></legend>

				<div class="input-wrap">
					<label for="field-state"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_STATE'); ?>:</label>
					<select name="fields[state]" id="field-state">
						<option value="0"<?php if ($this->row->get('state') == 0) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JUNPUBLISHED'); ?></option>
						<option value="1"<?php if ($this->row->get('state') == 1) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JPUBLISHED'); ?></option>
						<option value="2"<?php if ($this->row->get('state') == 2) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JTRASHED'); ?></option>
					</select>
				</div>

				<div class="input-wrap">
					<label for="field-access"><?php echo Lang::txt('COM_COLLECTIONS_FIELD_ACCESS'); ?>:</label>
					<select name="fields[access]" id="field-access">
						<option value="0"<?php if ($this->row->get('access') == 0) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COLLECTIONS_ACCESS_PUBLIC'); ?></option>
						<option value="1"<?php if ($this->row->get('access') == 1) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COLLECTIONS_ACCESS_REGISTERED'); ?></option>
						<option value="4"<?php if ($this->row->get('access') == 4) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COLLECTIONS_ACCESS_PRIVATE'); ?></option>
					</select>
				</div>
			</fieldset>
		</div>
	</div>

	<input type="hidden" name="fields[id]" value="<?php echo $this->row->get('id'); ?>" />
	<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
	<input type="hidden" name="task" value="save" />

	<?php echo Html::input('token'); ?>
</form>