<?php
/**
 * forums_TitleScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_TitleScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return forums_persistentdocument_title
     */
    protected function initPersistentDocument()
    {
    	return forums_TitleService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/title');
	}
}