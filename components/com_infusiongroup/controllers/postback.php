<?php defined('_JEXEC') or die('Restricted Access');

class InfusiongroupControllerPostback extends FOFController {

	public function execute($task) {
		switch($task) {
			case 'create_and_login_joomla_user':
				if(!$this->_verifyReferrer()) {
					return false;
				}
				$this->_register16(true);
				$followup_page = $this->config->get('complete_registration_redirect');
				JFactory::getApplication()->redirect($followup_page);
				return true;
			case 'create_joomla_user':
				if(!$this->_verifyReferrer()) {
					return false;
				}
				$this->_register16();
				return "User Created Successfully";
			case 'redirect_logged_in':
				JFactory::getApplication()->redirect($this->config->get('logged_in_redirect'));
				return true;
			default:
				return parent::execute($task);
		}
	}

	private function _verifyReferrer() {
		$referrer = $_SERVER['HTTP_REFERRER'];
		return (strpos($referrer,'infusionsoft') !== false);
	}

	private function _register16($login = false) {
		$app = JFactory::getApplication();
		$lang = JFactory::getLanguage();
		$lang->load('com_users',JPATH_SITE);

		$firstname = JRequest::getString('Contact0FirstName','InfusionSoft');
		$lastname =  JRequest::getString('Contact0LastName','User');
		$name = $firstname." ".$lastname;
		$email = JRequest::getString('Contact0Email','');
		$username = $email;
		$password = JUserHelper::genRandomPassword();

		JRequest::setVar('firstname',$firstname,'POST');
		JRequest::setVar('lastname',$lastname,'POST');
		JRequest::setVar('name',$name,'POST');
		JRequest::setVar('email',$email,'POST');
		JRequest::setVar('username',$username,'POST');
		JRequest::setVar('password',$password,'POST');
		JRequest::setVar('password_verify',$password,'POST');
		JRequest::setVar('password_clear',$password,'POST');

		// Attempt to save the data.
		$return	= $this->_register(JRequest::get('post'));

		// Check for errors.
		if ($return === false) :
			return false;
		else:
			if ($login) :
				// Set the return URL in the user state to allow modification by plugins
				$return_url = $this->config->get('auto_create_redirect');
				$app->setUserState('users.login.form.return', $return_url);

				// Get the log in options.
				$options = array();
				$options['remember'] = JRequest::getBool('remember', false);
				$options['return'] = $return;

				// Get the log in credentials.
				$credentials = array();
				$credentials['username'] = $email;
				$credentials['password'] = $password;

				// Perform the log in.
				if (true === $app->login($credentials, $options)) {
					// Success
					$app->setUserState('users.login.form.data', array());
					$app->redirect(JRoute::_($app->getUserState('users.login.form.return'), false));
				} else {
					// Login failed !
					$data['remember'] = (int)$options['remember'];
					//$app->setUserState('users.login.form.data', $data);
					$app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
				}
			endif;
		endif;
	}

	private function _register($temp, $useractivation=0) {
		$config = JFactory::getConfig();
		$params = JComponentHelper::getParams('com_users');
		$app 	= JFactory::getApplication();

		// Initialise the table with JUser.
		$user = new JUser;
		$system	= $params->get('new_usertype', 2);
		$data['groups'][] = $system;

		// Merge in the registration data.
		foreach ($temp as $k => $v) {
			$data[$k] = $v;
		}

		// Prepare the data for the user object.
		$data['email']		= $data['email'];
		$data['password']	= $data['password'];

		// Check if the user needs to activate their account.
		if (($useractivation == 1) || ($useractivation == 2)) {
			jimport('joomla.user.helper');
			$data['activation'] = JUtility::getHash(JUserHelper::genRandomPassword());
			$data['block'] = 1;
		}

		// Bind the data.
		if (!$user->bind($data)) {
			JError::raiseError(500, JText::sprintf('COM_USERS_REGISTRATION_BIND_FAILED', $user->getError()));
			return false;
		}

		// Load the users plugin group.
		JPluginHelper::importPlugin('user');

		// Store the data.
		if (!$user->save()) {
			JError::raiseError(500, JText::sprintf('COM_USERS_REGISTRATION_SAVE_FAILED', $user->getError()));
			return false;
		}

		// Compile the notification mail values.
		$data = $user->getProperties();
		$data['fromname']	= $config->get('fromname');
		$data['mailfrom']	= $config->get('mailfrom');
		$data['sitename']	= $config->get('sitename');
		$data['siteurl']	= JUri::base();

		// Handle account activation/confirmation emails.
		if ($useractivation == 2) {
			// Set the link to confirm the user email.
			$uri = JURI::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base.JRoute::_('index.php?option=com_users&task=registration.activate&token='.$data['activation'], false);

			$emailSubject = JText::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);
			$emailBody = JText::sprintf('COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY', $data['name'], $data['sitename'], $data['siteurl'].'index.php?option=com_users&task=registration.activate&token='.$data['activation'], $data['siteurl'], $data['username'], $data['password_clear']);
		} else if ($useractivation == 1) {
			// Set the link to activate the user account.
			$uri = JURI::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base.JRoute::_('index.php?option=com_users&task=registration.activate&token='.$data['activation'], false);

			$emailSubject	= JText::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);
			$emailBody = JText::sprintf('COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY', $data['name'], $data['sitename'], $data['siteurl'].'index.php?option=com_users&task=registration.activate&token='.$data['activation'], $data['siteurl'], $data['username'], $data['password_clear']);
		} else {
			$emailSubject	= JText::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);
			$emailBody = JText::sprintf('COM_USERS_EMAIL_REGISTERED_BODY', $data['name'], $data['sitename'], $data['siteurl'], $data['username'], $data['password_clear']);
		}

		// Send the registration email.
		$return = JUtility::sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

		// Check for an error.
		if ($return !== true) {
			$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_SEND_MAIL_FAILED'));

			// Send a system message to administrators receiving system mails
			$db = JFactory::getDBO();
			$q = 'SELECT id FROM #__users WHERE block = 0 AND sendEmail = 1';
			$db->setQuery($q);
			$sendEmail = $db->loadResultArray();
			if (count($sendEmail) > 0) {
				$jdate = new JDate();
				// Build the query to add the messages
				$q = 'INSERT INTO `#__messages` (`user_id_from`, `user_id_to`, `date_time`, `subject`, `message`) VALUES ';
				$messages = array();
				foreach ($sendEmail as $userid) {
					$messages[] = '('.$userid.', '.$userid.', '.$db->quote($jdate->toMySQL()).', '.$db->quote(JText::_('COM_USERS_MAIL_SEND_FAILURE_SUBJECT')).', '.$db->quote(JText::sprintf('COM_USERS_MAIL_SEND_FAILURE_BODY', $return, $data['username'])).')';
				}
				$q .= implode(', ', $messages);
				$db->setQuery($q);
				$db->query();
			}
			return false;
		}
	}
}