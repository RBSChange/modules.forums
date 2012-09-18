<?php
/**
 * forums_ThreadScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_ThreadScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return forums_persistentdocument_thread
	 */
	protected function initPersistentDocument()
	{
		return forums_ThreadService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/thread');
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