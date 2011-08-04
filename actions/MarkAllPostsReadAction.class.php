<?php
/**
 * forums_MarkAllPostsReadAction
 * @package modules.forums.actions
 */
class forums_MarkAllPostsReadAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if ($member !== null)
		{
			$member->markAllPostsAsRead();
		}
		
		$url = $request->getParameter('backUrl');
		if (!$url)
		{
			$url = $_SERVER['HTTP_REFERER'];
		}
		change_Controller::getInstance()->redirectToUrl($url);
	}
}