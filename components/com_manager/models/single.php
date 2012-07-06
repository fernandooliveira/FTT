<?php
# Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.model');

class JmbModelSingle extends JModel
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
         public function getTable($type = 'Single', $prefix = 'SingleTable', $config = array()) 
        {
                return JTable::getInstance($type, $prefix, $config);
        }
        /**
         * Get the message
         * @return string The message to be displayed to the user
         */
        public function getMsg() 
        {
                if (!isset($this->msg)) 
                {
                        $id = JRequest::getInt('id');
                        // Get a TableHelloWorld instance
                        $table = $this->getTable();
 
                        // Load the message
                        $table->load($id);
 
                        // Assign the message
                        $this->msg = $table;
                }
                return $this->msg;
        }
}
?>
