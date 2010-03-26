<?php
/**
 * forums_WebsitefolderScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_WebsitefolderScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return forums_persistentdocument_websitefolder
     */
    protected function initPersistentDocument()
    {
    	if (($website = $this->getComputedAttribute('byWebsite')) !== null)
    	{
    		return forums_WebsitefolderService::getInstance()->getByWebsite($website);
    	}
    	return forums_WebsitefolderService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/websitefolder');
	}
	
	public function endProcess()
	{
		$document = $this->getPersistentDocument();		
		foreach ($this->script->getChildren($this) as $child)
		{
			if ($child instanceof users_PermissionsScriptDocumentElement)
			{
				$child->setPermissions($document);
			}
		}
	}
}