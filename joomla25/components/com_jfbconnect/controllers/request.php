<?php
/**
 * @package		JFBConnect
 * @copyright (C) 2009-2012 by Source Coast - All rights reserved
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class JFBConnectControllerRequest extends JController
{

    function display()
    {
        exit;
    }

    function requestSent()
    {
        $jfbcRequestId = JRequest::getInt('jfbcId');
        $fbRequestId = JRequest::getString('requestId');
        $inToList = JRequest::getVar('to');

        // Get the from user id from the request
        $jfbcLibrary = JFBConnectFacebookLibrary::getInstance();
        $requestInfo = $jfbcLibrary->api('/'.$fbRequestId);
        $fbFrom = $requestInfo['from']['id'];

        // Not using the model, as we're doing a simple store.
        JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfbconnect'.DS.'tables');
        $data = array();
        $data['fb_request_id'] = $fbRequestId;
        $data['fb_user_from'] = $fbFrom;
        $data['jfbc_request_id'] = $jfbcRequestId;
        $data['created'] = JFactory::getDate()->toMySQL();
        $data['modified'] = null;
        
        foreach ($inToList as $fbTo)
        {
            $row = & JTable::getInstance('JFBConnectNotification', 'Table');
            $to = JFilterInput::clean($fbTo, 'ALNUM');
            $data['fb_user_to'] = $to;
            $row->save($data);
        }

        $app =& JFactory::getApplication();
        $app->close();
    }

}
