<?php
/**
 * @package modules.forums
 */
class forums_DeletePostAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
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
				change_Controller::getInstance()->redirectToUrl($url);
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		
		$url = website_WebsiteService::getInstance()->getCurrentWebsite()->getUrl();
		change_Controller::getInstance()->redirectToUrl($url);
	}
	
	/**
	 * @return Boolean
	 */
	public function isSecure()
	{
		return false;
	}
}