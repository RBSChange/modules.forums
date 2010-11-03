<?php
/**
 * @package modules.forums
 */
class forums_CloseThreadAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
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
				HttpController::getInstance()->redirectToUrl($link);
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