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
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$canDo = \Components\Citations\Helpers\Permissions::getActions('sponsor');

JToolBarHelper::title(JText::_('CITATIONS') . ': ' . JText::_('CITATION_SPONSORS'), 'citation.png');
if ($canDo->get('core.create'))
{
	JToolBarHelper::addNew();
}
JToolBarHelper::spacer();
JToolBarHelper::help('sponsors');
?>
<script type="text/javascript">
function submitbutton(pressbutton)
{
	if (pressbutton == 'cancel') {
		submitform(pressbutton);
		return;
	}
	// do field validation
	submitform(pressbutton);
}
</script>

<form action="<?php echo JRoute::_('index.php?option=' . $this->option); ?>" method="post" name="adminForm" id="adminForm">
	<table class="adminlist">
		<thead>
			<tr>
				<th><?php echo JText::_('CITATION_ID'); ?></th>
				<th><?php echo JText::_('CITATION_SPONSORS'); ?></th>
				<th><?php echo JText::_('CITATION_SPONSORS_LINK'); ?></th>
				<th><?php echo JText::_('CITATION_SPONSORS_IMAGE'); ?></th>
				<th><?php echo JText::_('CITATION_SPONSORS_ACTIONS'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (count($this->sponsors) > 0) : ?>
				<?php foreach ($this->sponsors as $sponsor) : ?>
					<tr>
						<td><?php echo $sponsor['id']; ?></td>
						<td><?php echo $sponsor['sponsor']; ?></td>
						<td><?php echo $sponsor['link']; ?></td>
						<td><?php echo $sponsor['image']; ?></td>
						<td>
							<?php if ($canDo->get('core.edit')) { ?>
								<a href="<?php echo JRoute::_('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $sponsor['id']); ?>"><?php echo JText::_('JACTION_EDIT'); ?></a> |
							<?php } ?>
							<?php if ($canDo->get('core.delete')) { ?>
								<a href="<?php echo JRoute::_('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=remove&id=' . $sponsor['id']); ?>"><?php echo JText::_('JACTION_DELETE'); ?></a>
							<?php } ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="5"><?php echo JText::_('COM_CITATIONS_SPONSORS_NO_RESULTS'); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
	<input type="hidden" name="task" value="" />

	<?php echo JHTML::_('form.token'); ?>
</form>