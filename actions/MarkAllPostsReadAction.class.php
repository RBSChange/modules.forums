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
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($user !== null)
		{
			$profile = forums_ForumsprofileService::getInstance()->getByAccessorId($user->getId(), true);
			$profile->markAllPostsAsRead();
		}
		
		$url = $request->getParameter('backUrl');
		if (!$url)
		{
			$url = $_SERVER['HTTP_REFERER'];
		}
		change_Controller::getInstance()->redirectToUrl($url);
	}
}