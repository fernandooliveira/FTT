<?php
class JMBInvitateClass {
	protected $host;
    protected $user;
	
	public function __construct(){
		$this->host = &FamilyTreeTopHostLibrary::getInstance();
        $this->user = $this->host->user->get();
	}

    protected function invite($facebook_id, $token){
        if(!$token){
            return false;
        }

        $sql = "SELECT value FROM #__mb_variables WHERE belongs=?";
        $this->host->ajax->setQuery($sql, $token);
        $rows = $this->host->ajax->loadAssocList();

        if($rows==null) return false;
        $args = explode(',', $rows[0]['value']);

        $sql = "UPDATE #__mb_individuals SET `fid`=?,`change` = NOW(), `join_time`= NOW(), `creator` = ?  WHERE id=?";
        $this->host->ajax->setQuery($sql, $facebook_id, $args[0], $args[0]);
        $this->host->ajax->query();

        $sql = "DELETE FROM #__mb_variables WHERE belongs=?";
        $this->host->ajax->setQuery($sql, $token);
        $this->host->ajax->query();

        $this->host->user->set($args[1], $args[0], 0);
        $this->host->user->setPermission('USER');
        $this->host->user->setAlias('myfamily');
        $this->host->user->setToken(0);
        return true;
        exit;
    }

    protected function _getSenderEmail($user){
        $sqlString = "SELECT id, name, email FROM #__users WHERE username = ?";
        $this->host->ajax->setQuery($sqlString, 'fb_'. $user->FacebookId);
        return $this->host->ajax->loadAssocList();
    }

    protected  function _getTarget($gedcom_id, $user){
        $objects = $this->host->usertree->getUser($user->TreeId, $user->Id, $gedcom_id);
        $sort = array();
        foreach($objects as $key => $object){
            if($key != 'length'){
                $id = $object['user']['gedcom_id'];
                $sort[$id] = $object;
            }
        }
        if(isset($sort[$gedcom_id])){
            $obj = $sort[$gedcom_id];
            if(!empty($obj['families'])){
                return $this->host->gedcom->individuals->get($obj['user']['gedcom_id']);
            } else {
                if(empty($obj['parents'])){
                    return $this->host->gedcom->individuals->get($obj['user']['gedcom_id']);
                } else {
                    $parentsFamilies = $obj['parents'];
                    foreach($parentsFamilies as $key => $parents){
                        if($key != 'length'){
                            $fatherId = $parents['father']['gedcom_id'];
                            $motherId = $parents['mother']['gedcom_id'];
                            if(!empty($father) && !empty($mother)){
                                $father = $this->host->usertree->getUser($user->TreeId, $user->Id, $fatherId);
                                $mother = $this->host->usertree->getUser($user->TreeId, $user->Id, $motherId);
                                $fatherFamiliesLength = (empty($father['families']))?0:$father['families']['length'];
                                $motherFamiliesLength = (empty($mother['families']))?0:$mother['families']['length'];
                                if($fatherFamiliesLength == $motherFamiliesLength){
                                    return $this->host->gedcom->individuals->get($fatherId);
                                } else if($fatherFamiliesLength > $motherFamiliesLength){
                                    return $this->host->gedcom->individuals->get($fatherId);
                                } else {
                                    return $this->host->gedcom->individuals->get($motherId);
                                }
                            } else {
                                if(empty($father)){
                                    return $this->host->gedcom->individuals->get($motherId);
                                } else {
                                    return $this->host->gedcom->individuals->get($fatherId);
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public function checkUser(){
        $sql_string = "SELECT i.id, i.fid as facebook_id, u.email
                        FROM #__mb_individuals AS i
                        LEFT JOIN #__jfbconnect_user_map AS map ON map.fb_user_id = i.fid
                        LEFT JOIN #__users AS u ON u.id = map.j_user_id
                        WHERE i.fid !=0";
        $this->host->ajax->setQuery($sql_string);
        $rows = $this->host->ajax->loadAssocList();

        $language = $this->host->getLangList('invitate');

        if(!empty($rows)){
            foreach($rows as $row){
                if($row['facebook_id'] != null && $row['facebook_id'] == $this->user->facebookId){
                    $individual = $this->host->gedcom->individuals->get($row['id']);
                    return json_encode(array('success'=>true, 'user'=>$individual, 'msg'=>$language));
                }
            }
        }
        if($this->user->token){
            $sql_string = "SELECT language, s_gedcom_id, value FROM #__mb_variables WHERE belongs = ?";
            $this->host->ajax->setQuery($sql_string, $this->user->token);
            $rows = $this->host->ajax->loadAssocList();
            if(!empty($rows)){
                $opt = explode(',', $rows[0]['value']);
                $user = $this->host->gedcom->individuals->get($rows[0]['s_gedcom_id']);
                //$target = $this->host->gedcom->individuals->get($opt[0]);
                $target = $this->_getTarget($opt[0], $user);
                $family = $this->host->usertree->getUser($user->TreeId, $user->Id,  $target->Id);
                $data = array(
                    'language' => $rows[0]['language'],
                    'sender' => $user,
                    'target' => $target,
                    'from' => $rows[0]['s_gedcom_id'],
                    'to' => $opt[0],
                    'relation' => $this->host->gedcom->relation->get($opt[1], $opt[0], $rows[0]['s_gedcom_id']),
                    'sender_data' => $this->_getSenderEmail($user)
                );
                return json_encode(array('success'=>false, 'sender'=>$user, 'family'=>$family, 'data' => $data, 'msg'=>$language));
            }
        }
        $this->host->user->setToken(0);
        $this->host->user->setAlias($this->user->facebookId, 'first-page');

        return json_encode(array('success'=>false, 'sender'=>false, 'msg'=>$language));
    }

    public function accept(){
        $facebookId = $this->user->facebookId;
        $token = $this->user->token;
        return $this->invite($facebookId, $token);
    }

    public function deny(){
        $sql_string = "DELETE FROM #__mb_variables WHERE belongs = ?";
        $this->host->ajax->setQuery($sql_string, $this->user->token);
        $this->host->ajax->query();

        $this->host->user->setToken(0);
        $this->host->user->setAlias($this->user->facebookId, 'first-page');
        return json_encode(array('success'=>true));
    }

    public function logout(){
        $user = $this->host->user->get();
        $this->host->user->clearToken();
        // Setup the logout settings
        $jfbcLibrary = JFBConnectFacebookLibrary::getInstance();

        $fbClient = $jfbcLibrary->getFbClient();
        $fbClient->destroySession();

        $app = JFactory::getApplication();
        $app->logout();

        return $user->token;
    }

    public function setInvitationLanguage($args){
        $args = explode(',', $args);
        $sql_string = "UPDATE  #__mb_variables SET  language = ? WHERE  belongs = ?";
        $this->host->ajax->setQuery($sql_string, $args[0], $args[1]);
        $this->host->ajax->query();
    }
}
?>