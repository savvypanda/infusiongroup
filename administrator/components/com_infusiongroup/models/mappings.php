<?php defined('_JEXEC') or die('Restricted Access');

class InfusiongroupModelMappings extends FOFModel {
	private $ihelper;

	public function __construct($config = array()) {
		$config=array_merge($config, array('table'=>'mappings'));

		parent::__construct($config);
	}

	private function getHelper() {
		if(!is_object($this->ihelper)) {
			require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_infusiongroup'.DS.'helpers'.DS.'infusiongroup.php');
			$this->ihelper = new InfusiongroupHelper();
		}
		return $this->ihelper;
	}

	public function getItemList($overrideLimits=false) {
		if(empty($this->list)) {
			$helper = $this->getHelper();
			if(!$helper->testIxConn()) {
				$this->list = array(0=>array(
					'Id' => 0,
					'GroupName' => JText::_('COM_INFUSIONGROUP_CONNECTION_FAILED'),
					'GroupDescription' =>JText::_('COM_INFUSIONGROUP_CONNECTION_FAILED_DETAILS'),
					'mappings'=>array()
				));
				return $this->list;
			}
			$tags = $helper->fetchAllIxTags();
			$this->total = count($tags);

			if(!$overrideLimits) {
				$limit = $this->getState('limit',20);
				$limitstart = $this->getState('limitstart',0);
				$tags = array_slice($tags, $limitstart, $limit);
			}

			//we need to transform the tags array into an associative array with the key being the tag Id
			$newtags = array();
			foreach($tags as $tag) {
				$tag['mappings'] = array();
				$newtags[$tag['Id']] = $tag;
			}

			//now we need to add in all of the mappings
			$query = 'SELECT * FROM #__infusiongroup_mappings';
			$this->_db->setQuery($query);
			$all_mappings = $this->_db->loadObjectList();

			$query = 'SELECT title, id FROM #__usergroups';
			$this->_db->setQuery($query);
			$all_usergroups = $this->_db->loadObjectList('id');

			foreach($all_mappings as $mapping) {
				$tag_id = $mapping->ix_tag_id;
				if(isset($newtags[$tag_id])) {
					$group_id = $mapping->group_id;
					$group_name = $all_usergroups[$group_id]->title;
					$newtags[$tag_id]['mappings'][] = $group_name;
				}
			}

			//finally we save our list
			$this->list = $newtags;
		}
		return $this->list;
	}

	public function getItem($id = null) {
		if(!is_null($id)) {
			$this->record = null;
			$this->id = $id;
		}

		if(empty($this->record) && !empty($this->id)) {
			$helper = $this->getHelper();
			if(!$helper->testIxConn()) {
				$this->id = 0;
				$item = new stdClass();
				$item->id = 0;
				$item->tag = JText::_('COM_INFUSIONGROUP_CONNECTION_FAILED');
				$item->GroupDescription = JText::_('COM_INFUSIONGROUP_CONNECTION_FAILED_DETAILS');
				$item->mappings = array();
				$item->free_mappings = array();
				$this->record = $item;
				return $this->record;
			}
			$tag = $helper->fetchIxTagById($this->id);

			$query = 'SELECT * FROM #__infusiongroup_mappings WHERE ix_tag_id='.$this->_db->quote($this->id);
			$this->_db->setQuery($query);
			$mappings = $this->_db->loadAssocList('group_id');

			$query = 'SELECT id, title FROM #__usergroups';
			$this->_db->setQuery($query);
			$all_groups = $this->_db->loadAssocList('id');

			$groups_taken = array();
			foreach($mappings as $mapping) {
				$mapping['group_name'] = $all_groups[$mapping['group_id']];
				$groups_taken[] = $mapping['group_id'];
			}
			$groups_taken = array_unique($groups_taken);
			foreach($groups_taken as $gid) {
				//here were are removing all of the groups that have already been mapped to make the backend more friendly
				unset($all_groups[$gid]);
			}

			$mapping_ids = array();
			foreach($mappings as $map) {
				$mapping_ids[] = $map['group_id'];
			}
			if(!empty($mapping_ids)) {
				$query = 'SELECT id, title FROM #__usergroups WHERE id IN('.implode(',',$mapping_ids).')';
				$this->_db->setQuery($query);
				$group_names = $this->_db->loadAssocList();
				foreach($group_names as $group) {
					$mappings[$group['id']]['group_name'] = $group['title'];
				}
			}

			$item = new stdClass();
			$item->id = $this->id;
			$item->tag = $tag['GroupName'];
			$item->GroupDescription = $tag['GroupDescription'];
			$item->mappings = $mappings;
			$item->free_mappings = $all_groups;

			$this->record = $item;
		}

		return $this->record;
	}

	//functions we have to override to make the model work without an actual table
	public function setIDsFromRequest() {
		return $this;
		//return parent::setIDsFromRequest();
	}
	public function checkout() {
		return true;
	}
	public function checkin() {
		return true;
	}
	public function getTotal() {
		//this function is worthless if we have not created the itemList yet. The total is saved in the getItemList function

		//if(empty($this->total)) {
			//$this->total = count($this->getItemList());
		//}
		return $this->total;
	}


	public function save() {
		//do stuff here
		return true;
	}

	public function delete() {
		//do stuff here
		return true;
	}

	public function remove() {
		JRequest::checkToken();
		$id = JRequest::getInt('mapping_id',0);
		if($id != 0) {
			$query = 'DELETE FROM #__infusiongroup_mappings WHERE infusiongroup_mapping_id='.$this->_db->quote($id);
			$this->_db->setQuery($query);
			$this->_db->query();
		}
		return true;
	}

	public function add() {
		JRequest::checkToken();
		$ix_tag_id = JRequest::getInt('id',0);
		$ix_tag_name = JRequest::getString('tag','');
		$group_id = JRequest::getInt('mapping_id',0);
		if(!empty($ix_tag_id) && !empty($ix_tag_name) && !empty($group_id)) {
			//sanity check
			$query = 'SELECT * FROM #__infusiongroup_mappings WHERE ix_tag_id='.$this->_db->quote($ix_tag_id).' AND group_id='.$this->_db->quote($group_id);
			$this->_db->setQuery($query);
			$this->_db->query();
			if($this->_db->getNumRows() != 0) {
				//this mapping already exists! Bail out
				return false;
			}

			$query = 'INSERT INTO #__infusiongroup_mappings(ix_tag_id, ix_tag_name, group_id) VALUES ('.$this->_db->quote($ix_tag_id).', '.$this->_db->quote($ix_tag_name).', '.$this->_db->quote($group_id).')';
			$this->_db->setQuery($query);
			$this->_db->query();
		}
	}
}