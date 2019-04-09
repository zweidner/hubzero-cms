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
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

Toolbar::title(Lang::txt('COM_CONFIG_GLOBAL_CONFIGURATION'), 'config');
Toolbar::apply('application.apply');
Toolbar::save('application.save');
Toolbar::divider();
Toolbar::cancel('application.cancel');
Toolbar::divider();
Toolbar::help('global_config');

// Load tooltips behavior
Html::behavior('formvalidation');
Html::behavior('switcher', 'submenu');
Html::behavior('tooltip');

$ignore = array(
	'app', 'site', 'offline', 'meta', 'seo', 'cookie', 'system',
	'debug', 'cache', 'session', 'server', 'locale', 'ftp', 'database',
	'mail', 'api', 'permissions', 'filters', 'rate_limit', 'asset_id'
);

$others = array();
foreach ($this->data as $section => $values):
	if (in_array($section, $ignore)):
		continue;
	endif;

	if (empty($values) || !is_array($values)):
		continue;
	endif;

	$this->others[$section] = $values;
endforeach;

// Load submenu template, using element id 'submenu' as needed by behavior.switcher
Document::setBuffer($this->loadTemplate('navigation'), 'modules', 'submenu');

Html::behavior('formvalidation');
Html::behavior('keepalive');

$this->js();
?>

<form action="<?php echo Route::url('index.php?option=com_config');?>" id="application-form" method="post" name="adminForm" class="form-validate" data-invalid-msg="<?php echo $this->escape(Lang::txt('JGLOBAL_VALIDATION_FORM_FAILED'));?>">
	<?php /*if ($this->ftp) : ?>
		<?php echo $this->loadTemplate('ftplogin'); ?>
	<?php endif;*/ ?>
	<div id="config-document" class="clearfix">
		<div id="page-site" class="tab">
			<div class="grid noshow">
				<div class="col span6">
					<?php echo $this->loadTemplate('site'); ?>
					<?php echo $this->loadTemplate('offline'); ?>
					<?php echo $this->loadTemplate('metadata'); ?>
				</div>
				<div class="col span6">
					<?php echo $this->loadTemplate('seo'); ?>
					<?php echo $this->loadTemplate('cookie'); ?>
				</div>
			</div>
		</div>
		<div id="page-system" class="tab">
			<div class="grid noshow">
				<div class="col span7">
					<?php echo $this->loadTemplate('system'); ?>
				</div>
				<div class="col span5">
					<?php echo $this->loadTemplate('debug'); ?>
					<?php echo $this->loadTemplate('cache'); ?>
					<?php echo $this->loadTemplate('session'); ?>
				</div>
			</div>
		</div>
		<div id="page-server" class="tab">
			<div class="grid noshow">
				<div class="col span7">
					<?php echo $this->loadTemplate('server'); ?>
					<?php echo $this->loadTemplate('locale'); ?>
					<?php echo $this->loadTemplate('messagequeue'); ?>
				</div>
				<div class="col span5">
					<?php echo $this->loadTemplate('database'); ?>
					<?php echo $this->loadTemplate('mail'); ?>
				</div>
			</div>
		</div>
		<div id="page-api" class="tab">
			<div class="noshow">
				<?php echo $this->loadTemplate('api'); ?>
			</div>
		</div>
		<div id="page-permissions" class="tab">
			<div class="noshow">
				<?php echo $this->loadTemplate('permissions'); ?>
			</div>
		</div>
		<div id="page-filters" class="tab">
			<div class="noshow">
				<?php echo $this->loadTemplate('filters'); ?>
			</div>
		</div>
		<?php
		foreach ($this->others as $section => $values):
			$this->section = $section;
			$this->values  = $values;
			?>
			<div id="page-<?php echo $section; ?>" class="tab">
				<div class="noshow">
					<?php echo $this->loadTemplate('other'); ?>
				</div>
			</div>
			<?php
		endforeach;
		?>
		<input type="hidden" name="task" value="" />
		<?php echo Html::input('token'); ?>
	</div>
</form>
