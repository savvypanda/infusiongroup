<?php defined('_JEXEC') or die('Restricted Access');

require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_infusiongroup'.DS.'helpers'.DS.'infusiongroup.php');

/**
 * Infusiongroup User plugin
 *
 * @package        Joomla.Plugin
 * @subpackage    Infusiongroup.joomla
 * @since        2.5
 */
class plgUserInfusiongroup extends JPlugin {
	private $_debug = false;

    /**
     * This method should handle any login logic and report back to the subject
     *
     * @param    array    $user        Holds the user data
     * @param    array    $options    Array holding options (remember, autoregister, group)
     *
     * @return    boolean    True on success
     * @since    1.5
     */
    public function onUserLogin($user_login, $options = array()) {
        jimport('joomla.user.helper');
        $user = $this->_getUser($user_login, $options);

        //re-sync with Infusionsoft
        $this->updateJoomlaUserInfusionId($user);
		$this->updateJoomlaUserGroups($user);

        return true;
    }


    /**
     * This method will return a user object
     *
     * If options['autoregister'] is true, if the user doesn't exist yet he will be created
     *
     * @param    array    $user        Holds the user data.
     * @param    array    $options    Array holding options (remember, autoregister, group).
     *
     * @return    object    A JUser object
     * @since    1.5
     */
    protected function _getUser($user, $options = array()) {
		$instance = JUser::getInstance();
		if($id = intval(JUserHelper::getUserId($user['username']))) {
			$instance->load($id);
			return $instance;
		}
	}
    

    /**
     * @param    string    $context    The context for the data
     * @param    int        $data        The user id
     * @param    object
     *
     * @return    boolean
     * @since    1.6
     */
    function onContentPrepareData($context, $data)
    {
      
        // Check we are manipulating a valid form.
        if (!in_array($context, array('com_users.user', 'com_admin.profile'))) {
            return true;
        }

        if (is_object($data))
        {
            $userId = isset($data->id) ? $data->id : 0;
            

            if (!isset($data->infusionsoft) and $userId > 0) {

                // Load the profile data from the database.
                $db = JFactory::getDbo();
                $db->setQuery(
                    'SELECT profile_key, profile_value FROM #__user_profiles' .
                    ' WHERE user_id = '.(int) $userId." AND profile_key LIKE 'infusionsoft.%'" .
                    ' ORDER BY ordering'
                );
                $results = $db->loadRowList();

                // Check for a database error.
                if ($db->getErrorNum())
                {
                    $this->_subject->setError($db->getErrorMsg());
                    return false;
                }

                // Merge the profile data.
                $data->infusionsoft = array();

                foreach ($results as $v)
                {
                    $k = str_replace('infusionsoft.', '', $v[0]);
                    $data->infusionsoft[$k] = $v[1];
                }
            }

            if (!JHtml::isRegistered('users.yesno')) {
                JHtml::register('users.yesno', array(__CLASS__, 'yesno'));
            }
        }

        return true;
    }    
    
    
  /**
     * @param    JForm    $form    The form to be altered.
     * @param    array    $data    The associated data for the form.
     *
     * @return    boolean
     * @since    1.6
     */
    function onContentPrepareForm($form, $data)
    {

		if (!($form instanceof JForm))
        {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }

        // Check we are manipulating a valid form.
        if (!in_array($form->getName(), array('com_users.user', 'com_admin.profile' ))) {
            return true;
        }

        // Add the registration fields to the form.
        JForm::addFormPath(dirname(__FILE__).'/profiles');
        $form->loadFile('profile', false);

        return true;           
    }
    function onUserAfterSave($data, $isNew, $result, $error)
    {
        
        $userId    = JArrayHelper::getValue($data, 'id', 0, 'int');
		
		$user = JFactory::getUser($userId);

        if ($userId && $result && isset($data['infusionsoft']) && (count($data['infusionsoft'])))
        {
            try
            {
                //Sanitize the date

                $db = JFactory::getDbo();
                $db->setQuery(
                    'DELETE FROM #__user_profiles WHERE user_id = '.$userId .
                    " AND profile_key LIKE 'infusionsoft.%'"
                );

                if (!$db->query()) {
                    throw new Exception($db->getErrorMsg());
                }

                $tuples = array();
                $order    = 1;

                foreach ($data['infusionsoft'] as $k => $v)
                {
                    $tuples[] = '('.$userId.', '.$db->quote('infusionsoft.'.$k).', '.$db->quote($v).', '.$order++.')';
                }

                $db->setQuery('INSERT INTO #__user_profiles VALUES '.implode(', ', $tuples));

                if (!$db->query()) {
                    throw new Exception($db->getErrorMsg());
                }

            }
            catch (JException $e)
            {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        //re-sync with Infusionsoft

        $this->updateJoomlaUserInfusionId($user);

        
		//attmpt to send their info to InfusionSoft if they don't already exist.
		InfusiongroupHelper::createInfusionUser($user);		
		
        $this->updateJoomlaUserInfusionId($user);
        $this->updateJoomlaUserGroups($user);
        
                                          

            

        return true;
    }    
    

    
    /**
     * Remove all user profile information for the given user ID
     *
     * Method is called after user data is deleted from the database
     *
     * @param    array        $user        Holds the user data
     * @param    boolean        $success    True if user was succesfully stored in the database
     * @param    string        $msg        Message
     */
    function onUserAfterDelete($user, $success, $msg)
    {
        if (!$success) {
            return false;
        }

        $userId    = JArrayHelper::getValue($user, 'id', 0, 'int');

        if ($userId)
        {
            try
            {
                $db = JFactory::getDbo();
                $db->setQuery(
                    'DELETE FROM #__user_profiles WHERE user_id = '.$userId .
                    " AND profile_key LIKE 'infusionsoft.%'"
                );

                if (!$db->query()) {
                    throw new Exception($db->getErrorMsg());
                }
            }
            catch (JException $e)
            {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }        
	
	public function updateJoomlaUserInfusionId ($user)
	{
		//get Infusion ID
		$is_user_id = InfusiongroupHelper::getInfusionId($user);
		InfusiongroupHelper::setJoomlaInfusionProfileData($user,'is_id',$is_user_id);
	}
   

	public function updateJoomlaUserGroups($user, $debug = false) {
		if(!$user->id) return false;

		//get the user group mappings
		$db = JFactory::getDbo();
		$query = 'SELECT ix_tag_id, group_id FROM #__infusiongroup_mappings';
		$db->setQuery($query);
		$group_map = $db->loadAssocList();

		//see what tags this user has
		$isGroups = InfusiongroupHelper::getInfusionGroups($user, $debug);

		//General group refresh method is to strip all groups managed by Infusionsoft plugin in the mapping table and then add back the groups they belong in

		//strip all managed groups
		foreach($group_map as $group) {
			InfusiongroupHelper::removeUserFromGroup($user,$group['group_id']);
			//TODO capture list of groups so we can note changes to groups in the log
		}

		//then add the ones they belong to back in
		$is_group_log = '';
		foreach ($isGroups as $isGroup) :
			$is_groupid = $isGroup['GroupId'];
			$is_group_log.= $isGroup['ContactGroup']."[".$is_groupid."]\n";
			foreach($group_map as $group) {
				if($group['ix_tag_id'] == $is_groupid) {
					InfusiongroupHelper::addUserToGroup($user,$group['group_id']);
				}
			}

			//TODO check previous group list to see if there are any changes (removals or additions) and log them
		endforeach;

        //now user groups should be up to date. Write tags to profile for cross reference
        InfusiongroupHelper::setJoomlaInfusionProfileData($user,'is_current_tags',$is_group_log) ;
         
        return true;
    }
    
    


    
    


    public static function yesno($value)
    {
        if ($value) {
            return JText::_('JYES');
        }
        else {
            return JText::_('JNO');
        }
    }

  
}
