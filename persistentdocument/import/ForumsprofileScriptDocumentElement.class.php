<?php
/**
 * forums_ForumsprofileScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_ForumsprofileScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return forums_persistentdocument_forumsprofile
     */
    protected function initPersistentDocument()
    {
    	return forums_ForumsprofileService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return forums_persistentdocument_forumsprofilemodel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/forumsprofile');
	}
}