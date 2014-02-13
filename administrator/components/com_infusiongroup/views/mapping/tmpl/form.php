<?php
defined('_JEXEC') or die();
?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_infusiongroup" />
	<input type="hidden" name="view" value="mapping" />
	<input type="hidden" name="task" id="adminFormTaskInput" value="" />
	<input type="hidden" name="id" value="<?=$this->item->id?>" />
	<input type="hidden" name="tag" value="<?=$this->item->tag?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

	<fieldset>
		<legend><?php echo JText::_('COM_INFUSIONGROUP_MAPPING_DETAILS')?></legend>
		<h1><?=$this->item->id?>: <?=$this->item->tag?></h1>
		<?php if(!empty($this->item->GroupDescription)) echo $this->item->GroupDescription; ?>
		<?php if(!empty($this->item->mappings)): ?>
			<h3><?=JText::_('COM_INFUSIONGROUP_EXISTING_MAPPINGS')?></h3>
			<?php foreach($this->item->mappings as $mapping): ?>
				<p><?=$mapping['group_name']?> - <a href="index.php?option=com_infusiongroup&view=mapping&task=rm&id=<?=$this->item->id?>&mapping_id=<?=$mapping['infusiongroup_mapping_id']?>&<?=JUtility::getToken()?>=1"><?=JText::_('COM_INFUSIONGROUP_REMOVE_MAPPING')?></a></p>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php if(!empty($this->item->free_mappings)): ?>
			<h3><?=JText::_('COM_INFUSIONGROUP_ADD_MAPPING')?></h3>
			<select name="mapping_id">
				<option>--select one--</option>
				<?php foreach($this->item->free_mappings as $group): ?>
					<option value="<?=$group['id']?>"><?=$group['title']?></option>
				<?php endforeach; ?>
			</select>
			<input type="submit" onclick="document.getElementById('adminFormTaskInput').value='add';return true;" value="<?=JText::_('COM_INFUSIONGROUP_SUBMIT')?>" />
		<?php endif; ?>
	</fieldset>
</form>