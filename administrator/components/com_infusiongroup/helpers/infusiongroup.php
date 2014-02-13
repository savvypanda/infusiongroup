<?php defined('_JEXEC') or die('Restricted Access');

class InfusiongroupHelper {
	protected static $_ix_app;

	public static function getIxApp() {
		if(!is_object(self::$_ix_app)) {
			require_once(dirname(__FILE__).DS.'isdk'.DS.'isdk.php');
			$myApp = new iSDK;
			if ($myApp->cfgCon("signup","kill")) {
				self::$_ix_app = $myApp;
			} else {
				//JError::raiseError(500,"Failed to connect to Infusionsoft");
				return false;
			}
		}
		return self::$_ix_app;
	}

	private static function getInfusionsoftApp() {
		return self::getIxApp();
	}

	/*
	 * Public Static Functions that should be implemented here.
	 * A cohesive naming convention is very important
	 *
	 * Ix = Infusionsoft
	 *
	 * testIxConn - returns whether or now we are able to successfully connection to infusionsoft
	 *
	 * fetchAllIxTags - fetches a list of all the user-defined tags in infusionsoft
	 * fetchIxTagById - fetches the details of an infusionsoft tag given its ID
	 * addIxTag - creates the specified tag in infusionsoft
	 * removeIxTag - removes the specified tag in infusionsoft
	 *
	 * fetchIxIdForUser - get the infusionsoft user ID of a Joomla user
	 * fetchIdForIxUser - get the Joomla user ID of an infusionsoft user
	 *
	 * fetchIxTagsForUser - fetches an array of all of the infusionsoft tags for a user
	 * addIxTagToUser - adds 1 tag to the user in infusionsoft
	 * setIxTagsForUser - resets all tags on the users infusionsoft profile
	 * removeIxTagFromUser - removes 1 tag from the user in infusionsoft
	 *
	 * fetchGroupsForUser - fetches an array of all of the Joomla users groups that have been assigned to a user
	 * resetGroupsForUser - resets the user's assigned Joomla user groups based on their infusionsoft tags
	 * addGroupToUser - assigns the user to 1 group
	 * removeGroupFromUser - removes 1 group assignment from the user
	 */

	public static function testIxConn() {
		return (self::getIxApp() !== false);
	}
	public static function fetchAllIxTags($limit = 1000, $page = 0) {
		$ix = self::getIxApp();
		$queryfields = array('Id'=>'%');
		$returnfields = array('Id','GroupName','GroupDescription');
		return $ix->dsQuery('ContactGroup',$limit,$page,$queryfields, $returnfields);
	}

	public static function fetchIxTagById($id) {
		$ix = self::getIxApp();
		$returnfields = array('Id','GroupName','GroupDescription');
		return $ix->dsLoad('ContactGroup',$id,$returnfields);
	}


	/*
	 * End of functions defined by Levi. The following functions were written by James
	 */


	public static function setInfusionTag ($user, $tag_id) {
		$contact_id = self::getInfusionID($user);
	
		//now we have the Contact ID we want to assign a new tag to them
		$result = self::getInfusionsoftApp()->grpAssign($contact_id, $tag_id);
		
		if ($result):
			return true;
		endif;
		return false;
	}	
	
	public static function applyActionSet ($user, $as_id) {

		$contact_id = self::getInfusionID($user);
	
		//now we have the Contact ID we want to assign a new tag to them
		$result = self::getInfusionsoftApp()->runAS((int)$contact_id, (int)$as_id);
		if ($result):
			return true;
		endif;
		return false;
	}		
	
    public static function getInfusionID ($user) {
        //first check if Infusionsoft Profile ID already exists
		
        //$InfuseID = self::getJoomlaInfusionProfileData($user,'is_id');
		
        //if ($InfuseID) :
        //    return $InfuseID;
        //else:
            
        //endif;
		
		return self::getInfusionIdByEmail($user->email);
    }
	
    public static function getJoomlaInfusionProfileData($user,$key) {
		
		
		
        // Load the profile data from the database.
        $db = JFactory::getDbo();
        $db->setQuery(
            'SELECT profile_value FROM #__user_profiles' .
            ' WHERE user_id = '.(int) $user->id." AND profile_key = 'infusionsoft.".$key."'" .
            ' ORDER BY ordering'
        );
        return $db->loadResult();
    }	
	
    public static function setJoomlaInfusionProfileData($user,$key,$value) {
        //write the updated information to their Joomla profile
        $sql = "REPLACE INTO #__user_profiles (user_id,profile_key,profile_value) VALUES ({$user->id}, 'infusionsoft.{$key}','{$value}')";     
        $db = JFactory::getDBO();
        $db->setQuery($sql);
        $db->query();

        return true;
    }	

    public static function getInfusionIdByEmail($user_email) {
        
       
        //get Infusionsoft Contact ID
        $returnFields = array('Id', 'FirstName', 'LastName');
        $data = self::getInfusionsoftApp()->findByEmail($user_email, $returnFields);
		
        $contactId = $data && isset($data[0]['Id']) ? $data[0]['Id'] : null;
        
		if ($contactId > 0) :
			return $contactId;
		else:
			return 0;
		endif;
		
    }	
	public static function createInfusionUser($user) {
            
			$contact_id = self::getInfusionID($user);
			
			
            if (!$contact_id) {
                //doesn't exist so add them
                $firstName = JRequest::getString('firstname',NULL);
                $lastName = JRequest::getString('lastname',NULL);    
				
				        
				if (!$firstName) {
					$jform = JRequest::getVar ('jform',NULL);
					$name_array = explode(" ",$user->name);
					$firstName = array_shift($name_array);
					$lastName = implode(" ",$name_array);
				} 
                
                $conDat = array('FirstName' => $firstName,
                                'LastName'  => $lastName,
                                'Username' => $user->username,
                                'Email'     => $user->email);
                $conID = self::getInfusionsoftApp()->addCon($conDat);            
				
            }
	
	
	}

    public static function getInfusionGroups ($user) {

		$is_user_id = self::getInfusionID($user);

        //now user Contact ID to get Groups (tags)
        $returnFields = array('GroupId','ContactId','ContactGroup','DateCreated');
        $query = array('ContactId' => $is_user_id);
        $isGroups = self::getInfusionsoftApp()->dsQuery('ContactGroupAssign',50,0,$query,$returnFields);
        
        return $isGroups;
    }
	public static function getInfusionAddress ($user) {
		$is_user_id = self::getInfusionID($user);

        //now user Contact ID to get info (tags)
        $returnFields = array('StreetAddress1','StreetAddress2','City','State','PostalCode','Country','Phone1','FirstName','LastName');
        $query = array('Id' => $is_user_id);
        $address = self::getInfusionsoftApp()->dsQuery('Contact',50,0,$query,$returnFields);
		
        return $address;
	}
	
    public static function addUserToGroup($user, $groupId)
    {
		
		$db = JFactory::getDBO();
		$db->setQuery("SELECT group_id FROM #__user_usergroup_map WHERE user_id = ".$user->id);
		$groups_array = $db->loadResultArray();
		
		
        // Add the user to the group if necessary.
        if (!in_array($groupId, $groups_array))
        {
            
            // Get the title of the group.
            $db    = JFactory::getDbo();
            $db->setQuery(
                'SELECT `title`' .
                ' FROM `#__usergroups`' .
                ' WHERE `id` = '. (int) $groupId
            );
            $title = $db->loadResult();
			

            // Check for a database error.
            if ($db->getErrorNum()) {
                return new JException($db->getErrorMsg());
            }

            // If the group does not exist, return an exception.
            if (!$title) {
                return new JException(JText::_('JLIB_USER_EXCEPTION_ACCESS_USERGROUP_INVALID'));
            }
           
           //add group
           $sql = "INSERT INTO #__user_usergroup_map VALUES (".$user->id.", ".$groupId.")";
           $db->setQuery($sql);
           $db->query();
        }
		
        return true;
    }	

    public static function removeUserFromGroup($user, $groupId)
    {
	   $db = JFactory::getDBO();	
	   //remove group
	   $sql = "DELETE FROM #__user_usergroup_map WHERE user_id =  ".$user->id." AND group_id = ".$groupId;
	   $db->setQuery($sql);
	   $db->query();
       return true;
    } 	
	
}

?>

