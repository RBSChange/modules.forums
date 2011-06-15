<?php
/**
 * forums_MarkAllPostsReadAction
 * @package modules.forums.actions
 */
class forums_MarkAllPostsReadAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
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
		HttpController::getInstance()->redirectToUrl($url);
	}
}