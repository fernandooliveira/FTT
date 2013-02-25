<?php
class JMBFamousFamily {
	protected $host;
	protected $db;
	
	public function __construct(){
		$this->host = &FamilyTreeTopHostLibrary::getInstance();
		$this->db = new JMBAjax();
	}
	
	protected function _getUserTreePermission($args){
		$sqlString = "SELECT type FROM #__mb_tree_links WHERE individuals_id = ? AND tree_id = ? LIMIT 1";
		$this->db->setQuery($sqlString, $args->Id, $args->TreeId);
		$rows = $this->db->loadAssocList();
		return $rows[0]['type'];
	}
	
	protected function _getFamilies(){
		$sqlString = "SELECT id, name, tree_id, individuals_id, permission FROM #__mb_famous_family";
		$this->db->setQuery($sqlString);
		$rows = $this->db->loadAssocList();
		return $rows;
	}
	
	protected function _getTreeKeepers(){
		$sqlString = "SELECT id, individuals_id, famous_family FROM #__mb_tree_keepers";
		$this->db->setQuery($sqlString);
		$rows = $this->db->loadAssocList();
		$result = array();
		if(!empty($rows)){
			foreach($rows as $row){
				$result[$row['individuals_id']] = $row;
			}
		}	
		return $result;
	}

    protected function getLiving($usertree){
        $count = 0;
        $type = gettype($usertree);
        if('array' == $type || 'object' == $type){
            foreach($usertree as $object){
                if(isset($object['user']) && $object['user']['death'] == null){
                    $birth = $object['user']['birth'];
                    if($birth != null){
                        $date = $birth['date'];
                        if($birth['date'] != null && $date[2] != null){
                            $turns = date("Y") - $date[2];
                            if($turns <= 120){
                                $count++;
                            }
                        } else {
                            $count++;
                        }
                    } else {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }
	
	public function getFamilies(){
		 $families = $this->_getFamilies();
		 $result = array();

		 foreach($families as $family){
             $tree_id = $family['tree_id'];
             $owner_id = $family['individuals_id'];
             $usertree = $this->host->usertree->load($tree_id, $owner_id);

             $count = sizeof($usertree);
             $living = $this->getLiving($usertree);
		 	 $ind = $this->host->gedcom->individuals->get($family['individuals_id']);
		 	 $avatar = $this->host->gedcom->media->getAvatarImage($family['individuals_id']);
		 	 $result[] = array('id'=>$family['id'],'name'=>$family['name'],'tree_id'=>$family['tree_id'],'individ'=>$ind,'descendants'=>$count,'living'=>$living,'avatar'=>$avatar);
		 }
		 $path = "";
         $language = $this->host->getLangList('famous_family_trees');
		 return json_encode(array('families'=>$result,'path'=>$path, 'msg'=>$language));
	}
	
	public function setFamilies($args){
		$args = json_decode($args);
        $this->host->user->set($args->TreeId, $args->Id, 1);
        $this->host->user->setAlias('myfamily');
		return true;
	}
}
?>
