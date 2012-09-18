<?php
/**
 * @package modules.forums
 */
class forums_CloseThreadAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		try
		{
			$thread = DocumentHelper::getDocumentInstance($request->getParameter('id'));
			if ($thread instanceof forums_persistentdocument_thread && $thread->isClosable())
			{
				$thread->setLocked(true);
				$thread->save();
				$link = LinkHelper::getDocumentUrl($thread);
				change_Controller::getInstance()->redirectToUrl($link);
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
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}
}