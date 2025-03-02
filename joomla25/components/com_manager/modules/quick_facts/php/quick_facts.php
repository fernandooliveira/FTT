<?php
class JMBQuickFacts {
	protected $host;
	
	public function __construct(){
		$this->host = &FamilyTreeTopHostLibrary::getInstance();
	}

	protected function getLanguage(){
		$lang = $this->host->getLangList('quick_facts');
		if(!$lang) return false;
		return $lang;
	}
	
	protected function getFacts($usertree){
        $count = 0;
        $youngest = 9999;
        $youngest_object = null;
        $oldest = 0;
        $oldest_object = null;
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
                                if($turns > $oldest){
                                    $oldest_object = $object;
                                    $oldest = $turns;
                                }
                                if($turns < $youngest){
                                    $youngest_object = $object;
                                    $youngest = $turns;
                                }
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
        return array('living'=>$count,'youngest'=>$youngest_object,'oldest'=>$oldest_object);
	}
	
	public function get(){
		//vars
        $user = $this->host->user->get();
        $owner_id = $user->gedcomId;
        $tree_id = $user->treeId;

		$usertree = $this->host->usertree->load($tree_id, $owner_id);
		
		//facts
		$count = sizeof($usertree);
		$facts = $this->getFacts($usertree);
		$user = $usertree[$owner_id];
		$language = $this->getLanguage();
		
		return json_encode(array(
			'language'=>$language,
			'user'=>$user,
			'count'=>$count,
			'living'=>$facts['living'],
			'youngest'=>$facts['youngest'],
			'oldest'=>$facts['oldest']
		));
	}
}
?>
