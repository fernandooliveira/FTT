<?php
# Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

# Require the com_content helper library
require_once(JPATH_COMPONENT.DS.'controller.php'); 

# include JS and CSS 
$document =& JFactory::getDocument();

$document->addStyleSheet('components/com_manager/js/jquery-ui.css');
$document->addStyleSheet('components/com_manager/js/jquery-colorpicker.css');

$document->addStyleSheet('components/com_manager/codebase/skins/dhtmlxlayout_dhx_skyblue.css');
$document->addStyleSheet('components/com_manager/codebase/skins/dhtmlxform_dhx_skyblue.css');
$document->addStyleSheet('components/com_manager/codebase/dhtmlxlayout.css');
$document->addStyleSheet('components/com_manager/codebase/dhtmlxtree.css');
$document->addStyleSheet('components/com_manager/codebase/dhtmlxtabbar.css');

$document->addScript('components/com_manager/codebase/dhtmlxcontainer.js');
$document->addScript('components/com_manager/codebase/dhtmlxcommon.js');
$document->addScript('components/com_manager/codebase/dhtmlxlayout.js');
$document->addScript('components/com_manager/codebase/dhtmlxtree.js');
$document->addScript('components/com_manager/codebase/dhtmlxform.js');
$document->addScript('components/com_manager/codebase/dhtmlxtabbar.js');

$css_code = 'html, body {
	    	width: 100%;
	        height: 100%;
	        margin: 0px;
	       
	}
	
	.dhxLayoutObj {
		width: 900px;
		height: 400px;
		position: relative;
	}

	.content_manager table{
		border:1px solid #000;
	}
	
	.content_manager tr{
		text-align: center;
		vertical-align: middle;
	}
	
	.content_manager td{
		text-align: center;
		vertical-align: middle;
	}
        #container{
            height: 100%;
            margin-bottom:0px;
            margin-top:0px;
        }
';

$document->addStyleDeclaration($css_code);

$document->addScript('components/com_manager/js/MyBranchesManager.js?111');
$document->addScript('components/com_manager/js/host.js?111');
$document->addScript('components/com_manager/js/core.js?111');

# Create the controller

$controller = new ManagerController( );

# Perform the Request task
$controller->execute( JRequest::getCmd('task'));

JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_manager'.DS.'tables');

# Redirect if set by the controller
$controller->redirect(); 

JHTML::_('behavior.mootools');

$document->addScript('components/com_manager/js/jquery.min.js');
$document->addScript('components/com_manager/js/jquery-ui.min.js');
$document->addScript('components/com_manager/js/jquery.form.js');

$document->addStyleSheet('../components/com_manager/js/jquery.autocomplete.css');
$document->addScript('../components/com_manager/js/jquery.autocomplete.min.js');

$document->addCustomTag( '<script type="text/javascript">jQuery.noConflict();</script>' );
?>
