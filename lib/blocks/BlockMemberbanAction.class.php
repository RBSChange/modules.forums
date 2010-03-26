<?php
/**
 * forums_BlockMemberbanAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockMemberbanAction extends website_TaggerBlockAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$post = $this->getDocumentParameter();
		if ($post->isBanable())
		{
			return $this->getInputViewName();
		}
		
		$agaviUser = Controller::getInstance()->getContext()->getUser();
		$agaviUser->setAttribute('illegalAccessPage', $_SERVER["REQUEST_URI"]);
		$this->forward('users', 'Authentication');
	}
	
	/**
	 * @return String
	 */
	function getInputViewName()
	{
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @return Array
	 */
	function getSubmitInputValidationRules()
	{
		return array('to{blank:false}', 'motif{blank:false}');
	}
	
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function executeSubmit($request, $response, forums_persistentdocument_ban $ban)
	{
		$ban->save();
		$this->sendBan($ban);
		
		$post = DocumentHelper::getDocumentInstance($request->getParameter('postid'));
		$url = $post->getPostUrlInThread();
		HttpController::getInstance()->redirectToUrl($url);
	}
	
	/**
	 * @param forums_persistentdocument_ban $ban
	 */
	private function sendBan($ban)
	{
		$ns = notification_NotificationService::getInstance();
		$notif = $ns->getNotificationByCodeName('modules_forums/ban');
		$recipients = new mail_MessageRecipients();
		$recipients->setTo($ban->getMember()->getEmail());
		$d = date_Converter::convertDateToLocal(date_Calendar::getInstance($ban->getTo()));
		$date = $d->getYear() . '-' . $d->getMonth() . '-' . $d->getDay();
		$params = array('PSEUDO' => $ban->getMember()->getLabel(), 'DATE' => $date, 'MOTIF' => $ban->display());
		$ns->send($notif, $recipients, $params, null);
	}
}