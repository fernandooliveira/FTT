<?php
# Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.model');

class JmbModelMobile extends JModel
{	
	 /**
         * @var string msg
         */
        protected $msg;
        
        /**
         * Returns a reference to the a Table object, always creating it.
         *
         * @param       type    The table type to instantiate
         * @param       string  A prefix for the table class name. Optional.
         * @param       array   Configuration array for model. Optional.
         * @return      JTable  A database object
         * @since       1.6
         */
         public function getTable($type = 'Mobile', $prefix = 'MobileTable', $config = array()){
                return JTable::getInstance($type, $prefix, $config);
        }
        /**
         * Get the message
         * @return string The message to be displayed to the user
         */
        public function getMsg(){
                if (!isset($this->msg)) 
                {
                        $obj = JRequest::get();
                        $objects = array();
                        $table = $this->getTable();
                        if($table){
                            foreach($obj['id'] as $id){
                                $table->load($id);
                                $objects[] = $table;
                            }
                        }
                        // Assign the message
                        $this->msg = $objects;
                }
                return $this->msg;
        }

        protected function _getModulesPath(){
            $jpath_base_explode = explode('/', JPATH_BASE);
            if(end($jpath_base_explode) == 'administrator'){
                array_pop($jpath_base_explode);
            }
            $jpath_base = implode('/', $jpath_base_explode);
            return $jpath_base.DS.'components'.DS.'com_manager'.DS.'modules';
        }

        protected function _getContent(){
            $db =& JFactory::getDBO();
            $sql_string = "SELECT content.id, content.layout_type, content.title, grid.json FROM #__mb_content as content LEFT JOIN #__mb_modulesgrid as grid ON grid.page_id = content.id";
            $db->setQuery($sql_string);
            return $db->loadAssocList();
        }

        protected function _getModules(){
            $db =& JFactory::getDBO();
            $sql_string = "SELECT id, name, title, description, is_system FROM #__mb_modules";
            $db->setQuery($sql_string);
            $modules_table = $db->loadAssocList();
            $mod_table = array();
            foreach($modules_table as $mod){
                $mod_table[$mod['id']] = $mod;
            }
            return $mod_table;
        }

        protected function _getPageId(){
            $objects = array();
            $loaded = JRequest::get();
            $table = $this->getTable();
            if($table){
                foreach($loaded['id'] as $id ){
                    $table->load($id);
                    $objects[$id] = $table;
                }
            }

            return $objects;
        }

        protected function _getFiles($dir){
            $files = array();
            if($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if($file!='.'&&$file!='..'&&$file!='index.html'){
                        $file_parts = explode('.', $file);
                        $end_file_part = end($file_parts);
                        if($end_file_part=='css'||$end_file_part=='js'){
                            $files[] = $file;
                        }
                    }
                }
            }
            return $files;
        }

        protected function _getIncludesFiles($moduleName){
            $modulesPath = $this->_getModulesPath();
            $modulePath = $modulesPath . DS . $moduleName;
            return array(
                'js' => $this->_getFiles( $modulePath . DS . 'js' ),
                'css' => $this->_getFiles( $modulePath . DS . 'css' )
            );
        }

        protected function _getModuleObjectName($moduleName){
            $nameParts = explode('_', $moduleName);
        	$name = 'JMB';
        	foreach($nameParts as $part){
                $name .= ucfirst(strtolower($part));
        	}
            $name .= 'Object';
        	return $name;
        }

        protected function _getModuleContainerId($moduleName){
            $nameParts = explode('_', $moduleName);
            $name = 'JMB';
        	foreach($nameParts as $part){
                $name .= ucfirst(strtolower($part));
        	}
            $name .= 'Container';
        	return $name;
        }

        protected function _getModulesGrid($json){
            $json_object = json_decode($json);
            $modules = $this->_getModules();
            $result = array();
            foreach($json_object as $td){
                if(is_object($td)){
                    foreach($td as $div){
                        if(is_object($div)){
                            $moduleName = $modules[$div->id]['name'];
                            $module = array();
                            $module['info'] = $modules[$div->id];
                            $module['files'] = $this->_getIncludesFiles($moduleName);
                            $module['object_name'] = $this->_getModuleObjectName($moduleName);
                            $module['container_id'] = $this->_getModuleContainerId($moduleName);
                            $result[$div->id] = $module;
                        }
                    }
                }
            }
            return $result;
        }

        public function getPageInfo(){
            $content = $this->_getContent();
            $ids = $this->_getPageId();
            $pages = array();
            foreach($content as $page){
                $page_id = $page['id'];
                if(isset($ids[$page_id])){
                    $pages[] = array(
                        'page_info' => $page,
                        'grid' => json_decode($page['json']),
                        'modules' => $this->_getModulesGrid($page['json'])
                    );
                }
            }
            return $pages;
        }

        public function getActiveTab(){
            $session = JFactory::getSession();
            $activeTab = $session->get('active_tab');
            if(empty($activeTab)){
                return '';
            } else {
                return $activeTab;
            }
        }
}
?>
