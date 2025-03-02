<?php
/**
* @version 1.4.0
* @package RSFirewall! 1.4.0
* @copyright (C) 2009-2013 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

class RSFirewallControllerList extends JControllerForm
{
	public function __construct() {
		parent::__construct();
	}
	
	protected function allowAdd($data = array()) {
		$user = JFactory::getUser();
		return $user->authorise('lists.manage', 'com_rsfirewall');
	}

	protected function allowEdit($data = array(), $key = 'id') {
		$user = JFactory::getUser();
		return $user->authorise('lists.manage', 'com_rsfirewall');
	}
	
	public function bulkAdd() {
		$this->setRedirect('index.php?option=com_rsfirewall&view=list&layout=bulk');
	}
	
	public function bulkSave() {
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		
		$app 	= JFactory::getApplication();
		$input	= $app->input;
		$model 	= $this->getModel('list');
		
		$data = $input->get('jform', '', 'array');
		$ips  = isset($data['ips']) ? $data['ips'] : '';
		$ips  = $this->explode($ips);
		
		unset($data['ips']);
		$added = 0;
		foreach ($ips as $ip) {
			if (!$ip) continue;
			
			$data['ip'] = $ip;			
			if (!$model->save($data)) {
				$app->enqueueMessage($model->getError(), 'error');
			} else {
				$added++;
			}
		}
		
		$this->setMessage(JText::sprintf('COM_RSFIREWALL_BULK_ITEM_SAVED_OK', $added));
		$this->setRedirect('index.php?option=com_rsfirewall&view=lists');
	}
	
	protected function explode($string) {
		$string = str_replace(array("\r\n", "\r"), "\n", $string);
		return explode("\n", $string);
	}
}