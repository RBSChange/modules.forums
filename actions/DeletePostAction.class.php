<?php
/**
 * @package modules.forums
 */
class forums_DeletePostAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		try
		{
			$post = DocumentHelper::getDocumentInstance($request->getParameter('id'));
			if ($post instanceof forums_persistentdocument_post && $post->isDeletable())
			{
				$post->setDeletedby(forums_MemberService::getInstance()->getCurrentMember());
				$post->setDeleteddate(date_Calendar::now()->toString());
				$post->save();
				$url = $post->getPostUrlInThread();
				HttpController::getInstance()->redirectToUrl($url);
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		
		$url = website_WebsiteModuleService::getInstance()->getCurrentWebsite()->getUrl();
		HttpController::getInstance()->redirectToUrl($url);
	}
	
	/**
	 * @return Boolean
	 */
	public function isSecure()
	{
		return false;
	}
}