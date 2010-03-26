<?php
/**
 * forums_BanScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_BanScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return forums_persistentdocument_ban
     */
    protected function initPersistentDocument()
    {
    	return forums_BanService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/ban');
	}
}