<?php
/**
 * forums_ForumgroupScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_ForumgroupScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return forums_persistentdocument_forumgroup
     */
    protected function initPersistentDocument()
    {
    	return forums_ForumgroupService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/forumgroup');
	}
	
	/**
	 * @param import_ScriptExecuteElement $scriptExecute
	 */
	public function setDocumentIdAttributeWithTopic($scriptExecute)
	{
		$this->script->setAttribute('byDocumentId', $this->getPersistentDocument()->getTopic()->getId());
	}
}