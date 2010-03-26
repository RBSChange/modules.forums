<?php
/**
 * forums_RankScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_RankScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return forums_persistentdocument_rank
     */
    protected function initPersistentDocument()
    {
    	return forums_RankService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/rank');
	}
}