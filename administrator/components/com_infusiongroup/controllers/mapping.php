<?php defined('_JEXEC') or die('Restricted Access');

class InfusiongroupControllerMapping extends FOFController {
	function execute($task) {
		if($task == 'rm') {
			$model = $this->getThisModel();
			$model->remove();
			$task = 'edit';
			$model->setState('task',$task);
		} else if($task == 'add') {
			$model = $this->getThisModel();
			$model->add();
			$task = 'edit';
			$model->setState('task',$task);
		}
		parent::execute($task);
	}
}