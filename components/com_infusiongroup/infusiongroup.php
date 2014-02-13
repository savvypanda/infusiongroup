<?php
if(!defined('_JEXEC')) die('Restricted Access');
include_once JPATH_LIBRARIES.DS.'fof'.DS.'include.php';
if(!defined('FOF_INCLUDED')) {
	JError::raiseError('500','FOF is not installed');
}

FOFDispatcher::getTmpInstance('com_infusiongroup')->dispatch();