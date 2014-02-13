<?php defined('_JEXEC') or die('Restricted Access');

class InfusiongroupToolbar extends FOFToolbar {
	public function onBrowse() {
		parent::onBrowse();

		//adding the parameters button
		if(JFactory::getUser()->authorise('core.admin', 'com_infusiongroup')) {
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_infusiongroup');
		}
	}
}