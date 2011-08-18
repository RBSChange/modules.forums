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
	public function execute($request, $response)
	{
		$post = $this->getDocumentParameter();
		if ($this->isInBackofficeEdition() || $post === null)
		{
			return website_BlockView::NONE;
		}
		
		if ($post->isBanable())
		{
			$this->setCommonValues($request);
			return $this->getInputViewName();
		}
		
		change_Controller::getInstance()->getStorage()->writeForUser('users_illegalAccessPage', $_SERVER["REQUEST_URI"]);
		return $this->forward('users', 'Authentication');
	}
	
	/**
	 * @param f_mvc_Request $request
	 */
	public function onValidateInputFailed($request)
	{
		$this->setCommonValues($request);
	}
	
	/**
	 * @param f_mvc_Request $request
	 */
	protected function setCommonValues($request)
	{
		$post = $this->getDocumentParameter();
		$request->setAttribute('post', $post);
		$request->setAttribute('bans', forums_BanService::getInstance()->getBansForUser($post->getPostauthor()));	
	}
	
	/**
	 * @return String
	 */
	public function getInputViewName()
	{
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @return Array
	 */
	public function getSubmitInputValidationRules()
	{
		return array('to{blank:false}', 'motif{blank:false}');
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeSubmit($request, $response, forums_persistentdocument_ban $ban)
	{
		$ban->save();
		$this->sendBan($ban);
		
		$post = DocumentHelper::getDocumentInstance($request->getParameter('postid'));
		$url = $post->getPostUrlInThread();
		change_Controller::getInstance()->redirectToUrl($url);
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