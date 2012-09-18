<?php
/**
 * forums_ForumScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_ForumScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return forums_persistentdocument_forum
	 */
	protected function initPersistentDocument()
	{
		$document = forums_ForumService::getInstance()->getNewDocumentInstance();
		if (isset($this->attributes['mountParent-refid']))
		{
			$document->setMountParent($this->getComputedAttribute('mountParent'));
		}
		return $document;
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/forum');
	}
}