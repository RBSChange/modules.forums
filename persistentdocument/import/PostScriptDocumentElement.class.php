<?php
/**
 * forums_PostScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_PostScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return forums_persistentdocument_post
	 */
	protected function initPersistentDocument()
	{
		return forums_PostService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/post');
	}
		
	/**
	 * @return void
	 */
	public function endProcess()
	{
		$document = $this->getPersistentDocument();
		if ($document->getPublicationstatus() == 'DRAFT')
		{
			$document->getDocumentService()->activate($document->getId());
		}
	}
}