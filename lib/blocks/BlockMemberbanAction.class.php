<?php
/**
 * forums_BlockMemberbanAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockMemberbanAction extends website_TaggerBlockAction
{
	/**
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
		return $this->forward('users', 'Authentication');
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
		$member = $ban->getMember();
		$ms = $member->getDocumentService();
		$notif = $ns->getConfiguredByCodeName('modules_forums/ban', $ms->getWebsiteId($member), $member->getLang());
		if ($notif instanceof notification_persistentdocument_notification)
		{
			$user = $member->getUser();
			$callback = array($ban->getDocumentService(), 'getNotificationParameters');
			$user->getDocumentService()->sendNotificationToUserCallback($notif, $user, $callback, $ban);
		}
	}
}