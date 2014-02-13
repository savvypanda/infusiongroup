<?php defined('_JEXEC') or die('Restricted Access'); ?>
<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" id="option" value="com_infusiongroup" />
	<input type="hidden" name="view" id="view" value="mappings" />
	<input type="hidden" name="task" id="task" value="browse" />
	<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

	<table class="adminlist">
		<thead>
			<tr>
				<th></th>
				<th><?php echo JHTML::_('grid.sort', 'COM_INFUSIONGROUP_MAPPINGS_FIELD_INFUSIONSOFT_TAG_ID', 'Tag ID', $this->lists->order_Dir, $this->lists->order) ?></th>
				<th><?php echo JHTML::_('grid.sort', 'COM_INFUSIONGROUP_MAPPINGS_FIELD_INFUSIONSOFT_TAG_NAME', 'Tag Name', $this->lists->order_Dir, $this->lists->order) ?></th>
				<th><?php echo JHTML::_('grid.sort', 'COM_INFUSIONGROUP_MAPPINGS_FIELD_GROUP_MAPPINGS', 'Group Mappings', $this->lists->order_Dir, $this->lists->order) ?></th>
				<th><?php echo JHTML::_('grid.sort', 'COM_INFUSIONGROUP_MAPPINGS_FIELD_INFUSIONSOFT_TAG_DESCRIPTION', 'Tag Description', $this->lists->order_Dir, $this->lists->order) ?></th>

			</tr>
		</thead>

		<tfoot>
			<tr>
				<td colspan="20">
					<?php if($this->pagination->total > 0) echo $this->pagination->getListFooter() ?>
				</td>
			</tr>
		</tfoot>

		<tbody>
			<?php if($count = count($this->items)): ?>
			<?php $m = 1; ?>
			<?php foreach ($this->items as $item) : ?>
				<?php
					$m = 1-$m;
					$link = 'index.php?option=com_infusiongroup&view=mapping&id='.$item['Id'];
				?>
				<tr class="<?php echo 'row'.$m; ?>">
					<td></td>
					<td><a href="<?=$link?>"><?=$item['Id']?></a></td>
					<td><a href="<?=$link?>"><?=$item['GroupName']?></a></td>
					<td><?=(empty($item['mappings'])?'':implode(', ',$item['mappings']))?></td>
					<td><a href="<?=$link?>"><?=$item['GroupDescription']?></a></td>
				</tr>
				<?php endforeach; ?>
			<?php else: ?>
			<tr>
				<td colspan="20">
					<?php echo  JText::_('COM_INFUSIONGROUP_NO_TAGS_FOUND'); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</form>