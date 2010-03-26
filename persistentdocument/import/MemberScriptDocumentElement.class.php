<?php
/**
 * forums_MemberScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_MemberScriptDocumentElement extends import_ScriptDocumentElement
{
	private $initByLogin;
	
    /**
     * @return customer_persistentdocument_customer
     */
    protected function initPersistentDocument()
    {
    	if ($this->initByLogin)
    	{
    		$member = forums_MemberService::getInstance()->createQuery()->add(Restrictions::eq('user.login', $this->initByLogin))->findUnique();
    		if (!$member)
    		{
    			throw new Exception('Invalid login : ' . $this->initByLogin);
    		}
    		return $member;	
    	}
    	return forums_MemberService::getInstance()->getNewDocumentInstance();
    }
	
	/**
	 * @see import_ScriptDocumentElement::getPersistentDocument()
	 *
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getPersistentDocument()
	{
		if (isset($this->attributes['byLogin']))
		{
			$this->initByLogin = $this->attributes['byLogin'];
			unset($this->attributes['byLogin']);	
		}
		return parent::getPersistentDocument();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/member');
	}
}