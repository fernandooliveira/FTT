<?php
/**
 * @package		Joomla.Site
 * @subpackage	Contact
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

class ContactControllerContact extends JControllerForm
{
	public function getModel($name = '', $prefix = '', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, array('ignore_request' => false));
	}

	public function submit()
	{
		// Check for request forgeries.
		JRequest::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app	= JFactory::getApplication();
		$model	= $this->getModel('contact');
		$params = JComponentHelper::getParams('com_contact');
		$stub	= JRequest::getString('id');
		$id		= (int)$stub;

		// Get the data from POST
		$data = JRequest::getVar('jform', array(), 'post', 'array');

		$contact = $model->getItem($id);


		$params->merge($contact->params);

		// Check for a valid session cookie
		if($params->get('validate_session', 0)) {
			if(JFactory::getSession()->getState() != 'active'){
				JError::raiseWarning(403, JText::_('COM_CONTACT_SESSION_INVALID'));

				// Save the data in the session.
				$app->setUserState('com_contact.contact.data', $data);

				// Redirect back to the contact form.
				$this->setRedirect(JRoute::_('index.php?option=com_contact&view=contact&id='.$stub, false));
				return false;
			}
		}

		// Contact plugins
		JPluginHelper::importPlugin('contact');
		$dispatcher	= JDispatcher::getInstance();

		// Validate the posted data.
		$form = $model->getForm();
		if (!$form) {
			JError::raiseError(500, $model->getError());
			return false;
		}

		$validate = $model->validate($form, $data);

		if ($validate === false) {
			// Get the validation messages.
			$errors	= $model->getErrors();
			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
				if ($errors[$i] instanceof Exception) {
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				} else {
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$app->setUserState('com_contact.contact.data', $data);

			// Redirect back to the contact form.
			$this->setRedirect(JRoute::_('index.php?option=com_contact&view=contact&id='.$stub, false));
			return false;
		}

		// Validation succeeded, continue with custom handlers
		$results	= $dispatcher->trigger('onValidateContact', array(&$contact, &$data));

		foreach ($results as $result) {
			if ($result instanceof Exception) {
				return false;
			}
		}

		// Passed Validation: Process the contact plugins to integrate with other applications
		$results = $dispatcher->trigger('onSubmitContact', array(&$contact, &$data));

		// Send the email
		$sent = false;
		if (!$params->get('custom_reply')) {
			$sent = $this->_sendEmail($data, $contact);
		}

		// Set the success message if it was a success
		if (!JError::isError($sent)) {
			$msg = JText::_('COM_CONTACT_EMAIL_THANKS');
		}

		// Flush the data from the session
		$app->setUserState('com_contact.contact.data', null);

		// Redirect if it is set in the parameters, otherwise redirect back to where we came from
		if ($contact->params->get('redirect')) {
			$this->setRedirect($contact->params->get('redirect'), $msg);
		} else {
			$this->setRedirect(JRoute::_('index.php?option=com_contact&view=contact&id='.$stub, false), $msg);
		}

		return true;
	}

	private function _sendEmail($data, $contact)
	{
			$app		= JFactory::getApplication();
			$params 	= JComponentHelper::getParams('com_contact');
			if ($contact->email_to == '' && $contact->user_id != 0) {
				$contact_user = JUser::getInstance($contact->user_id);
				$contact->email_to = $contact_user->get('email');
			}
			$mailfrom	= $app->getCfg('mailfrom');
			$fromname	= $app->getCfg('fromname');
			$sitename	= $app->getCfg('sitename');
			$copytext 	= JText::sprintf('COM_CONTACT_COPYTEXT_OF', $contact->name, $sitename);

			$name		= $data['contact_name'];
			$email		= $data['contact_email'];
			$subject	= $data['contact_subject'];
			$body		= $data['contact_message'];

			// Prepare email body
			$prefix = JText::sprintf('COM_CONTACT_ENQUIRY_TEXT', JURI::base());
			$body	= $prefix."\n".$name.' <'.$email.'>'."\r\n\r\n".stripslashes($body);
			
			//-------------------- Hack by Sankuru ------------------------------------------------------------------------------------------------
				$angkor_file = JPATH_SITE.DS.'administrator'.DS.'components'.DS.'com_angkor'.DS.'helper'.DS.'helper.php';
				if(file_exists($angkor_file))
				{
					require_once($angkor_file);
					$data['contact']=$contact;
					$angkor_email = angkor_Helper::get_angkor_email('SEND_MSG_TO_CONTACT',$data);
					
					if($angkor_email)
					{
						$emailSubject  = $angkor_email->subject;
						$emailBody  = $angkor_email->body;
						$body  = $emailBody;
						$mailfrom = $angkor_email->sender_email;
						$fromname = $angkor_email->sender_name;
					}
				}
			//-------------------- Hack by Sankuru ------------------------------------------------------------------------------------------------
			$mail = JFactory::getMailer();		
			$mail->addRecipient($contact->email_to);
			$mail->addReplyTo(array($email, $name));
			$mail->setSender(array($mailfrom, $fromname));
			
			$mail->setSubject($emailSubject);			
			$mail->setBody($body);
			
			//-------------------- Start Hack by Sankuru ------------------------------------------------------------------------------------------------
			if(class_exists('angkor_Helper'))
			{
				$mail->IsHTML(true);
				$mail->AltBody = angkor_Helper::getAltBody($mail->Body);
				$mail->MsgHTML($mail->Body);
			}
			//-------------------- End Hack by Sankuru ------------------------------------------------------------------------------------------------
			$sent = $mail->Send();

			//If we are supposed to copy the sender, do so.

			// check whether email copy function activated
			if ( array_key_exists('contact_email_copy',$data)  ) {
				$copytext		= JText::sprintf('COM_CONTACT_COPYTEXT_OF', $contact->name, $sitename);
				$copytext		.= "\r\n\r\n".$body;
				$copysubject	= JText::sprintf('COM_CONTACT_COPYSUBJECT_OF', $subject);
				
				//-------------------- Hack by Sankuru ------------------------------------------------------------------------------------------------
					$angkor_file = JPATH_SITE.DS.'administrator'.DS.'components'.DS.'com_angkor'.DS.'helper'.DS.'helper.php';
					if(file_exists($angkor_file))
					{
						require_once($angkor_file);
						$data['contact']=$contact;
						$angkor_email = angkor_Helper::get_angkor_email('SEND_COPY_MSG_TO_USER',$data);
						
						if($angkor_email)
						{
							$copysubject  = $angkor_email->subject;
							$copytext  = $angkor_email->body;
							$mailfrom = $angkor_email->sender_email;
							$fromname = $angkor_email->sender_name;
						}
					}
				//-------------------- Hack by Sankuru ------------------------------------------------------------------------------------------------
				$mail = JFactory::getMailer();
				$mail->addRecipient($email);
				$mail->addReplyTo(array($email, $name));
				$mail->setSender(array($mailfrom, $fromname));
				$mail->setSubject($copysubject);
				$mail->setBody($copytext);
				//-------------------- Start Hack by Sankuru ------------------------------------------------------------------------------------------------
				if(class_exists('angkor_Helper'))
				{
					$mail->IsHTML(true);
					$mail->AltBody = angkor_Helper::getAltBody($mail->Body);
					$mail->MsgHTML($mail->Body);
				}
				//-------------------- End Hack by Sankuru ------------------------------------------------------------------------------------------------
				$sent = $mail->Send();
			}
			
			/*------------------- Added by Sankuru------------------------------------------------------------------
			* Send copy of contact email to all system users
			*/
				$angkor_file = JPATH_SITE.DS.'administrator'.DS.'components'.DS.'com_angkor'.DS.'helper'.DS.'helper.php';
				if(file_exists($angkor_file))
				{
					require_once($angkor_file);
					$data['contact']=$contact;		
					
					$rows =angkor_Helper::get_all_system_users();
					foreach($rows as $row)
					{
						$data['adminname']=$row->name;
						$angkor_email = angkor_Helper::get_angkor_email('SEND_COPY_MSG_TO_ADMIN',$data);		
						
						if($angkor_email)
						{
							$copysubject  = $angkor_email->subject;
							$copytext  = $angkor_email->body;
							$mailfrom = $angkor_email->sender_email;
							$fromname = $angkor_email->sender_name;
						}
						
						$mail = JFactory::getMailer();
						$mail->addRecipient($row->email);
						$mail->addReplyTo(array($email, $name));
						$mail->setSender(array($mailfrom, $fromname));
						$mail->setSubject($copysubject);
						$mail->setBody($copytext);
						//-------------------- Start Hack by Sankuru ------------------------------------------------------------------------------------------------
						if(class_exists('angkor_Helper'))
						{
							$mail->IsHTML(true);
							$mail->AltBody = angkor_Helper::getAltBody($mail->Body);
							$mail->MsgHTML($mail->Body);
						}
						//-------------------- End Hack by Sankuru ------------------------------------------------------------------------------------------------
						$sent = $mail->Send();
					}
				}				
			//------------------- End Added by Sankuru------------------------------------------------------------------
			return $sent;
	}
}