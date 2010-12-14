<?php
/**
 * forums_ViewFeedAction
 * @package modules.forums.actions
 */
class forums_ViewFeedAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{		
		$parentId = $request->getModuleParameter('forums', 'parentref');
		if (null === $parentId)
		{
			$parentId = $request->getParameter('parentref');
		}
		if ($parentId !== null)
		{
			$parent = DocumentHelper::getDocumentInstance($parentId);
		}
		else
		{
			$parent = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		}
		
		$docType = $request->getModuleParameter('forums', 'docType');
		if (null === $docType)
		{
			$docType = $request->getParameter('docType');
		}
		if ($docType == 'thread')
		{
			$docType = 'thread';
			$documentService = forums_ThreadService::getInstance();
		}
		else
		{
			$docType = 'post';
			$documentService = forums_PostService::getInstance();
		}
		
		$recursive = $request->getModuleParameter('forums', 'recursive');
		if (null === $recursive)
		{
			$recursive = $request->getParameter('recursive');
		}
		$recursive = ($recursive == 'true') ? true : false;
		
		$feedWriter = $documentService->getRSSFeedWriterByParent($parent, $recursive);
		
		// Set the link, title and description of the feed.
		$this->setHeaders($feedWriter, $parent, $docType);
		$this->setContentType('text/xml');
		echo $feedWriter->toString();
	}
	
	/**
	 * @param rss_FeedWriter $feedWriter
	 * @param f_persistentdocument_PersistentDocument $parent
	 * @param String $docType
	 */
	private function setHeaders($feedWriter, $parent, $docType)
	{
		$modelName = $parent->getPersistentModel()->getDocumentName();
		$title = f_Locale::translate('&modules.forums.frontoffice.' . ucfirst($docType).'s-of-' . $modelName . 'Label;') . ' ' . $parent->getLabelAsHtml();
		$feedWriter->setTitle($title);
		if (f_util_ClassUtils::methodExists($parent, 'getRSSDescription'))
		{
			$feedWriter->setDescription(f_util_StringUtils::htmlToText($parent->getRSSDescription()));
		}
		elseif (f_util_ClassUtils::methodExists($parent, 'getDescriptionAsHtml'))
		{
			$feedWriter->setDescription(f_util_StringUtils::htmlToText($parent->getDescriptionAsHtml()));
		}
		else 
		{
			$feedWriter->setDescription('');
		}
		$feedWriter->setLink(LinkHelper::getUrl($parent));
	}
	
	public function isSecure()
	{
		return false;
	}
	
	protected function suffixSecureActionByDocument()
	{
		return false;
	}
}