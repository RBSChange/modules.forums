<?php
/**
 * Class where to put your custom methods for document forums_persistentdocument_forumgroup
 * @package modules.forums.persistentdocument
 */
class forums_persistentdocument_forumgroup extends forums_persistentdocument_forumgroupbase implements indexer_IndexableDocument
{
	/**
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocument()
	{
		$indexedDoc = new indexer_IndexedDocument();
		$indexedDoc->setId($this->getId());
		$indexedDoc->setDocumentModel('modules_forums/forum');
		$indexedDoc->setLabel($this->getLabel());
		$indexedDoc->setLang(RequestContext::getInstance()->getLang());
		$indexedDoc->setText($this->getFullTextForIndexation());
		return $indexedDoc;
	}
	
	/**
	 * @return String
	 */
	private function getFullTextForIndexation()
	{
		return f_util_StringUtils::htmlToText($this->getDescriptionAsHtml());
	}
	
	/**
	 * @return String
	 */
	public function getKeywords()
	{
		if ($this->getMetakeywords())
		{
			return $this->getMetakeywords();
		}
		return str_replace(' ', ', ', $this->getLabel()).', '.f_Locale::translate('&modules.forums.meta.forum;');
	}
	
	/**
	 * @var f_persistentdocument_PersistentDocument $parent instance of topic or website
	 */
	private $parent = null;

	/**
	 * @return f_persistentdocument_PersistentDocument instance of topic or website
	 */
	public function getMountParent()
	{
		if ($this->parent !== null)
		{
			return $this->parent;
		}
		$topic = $this->getTopic();
		if ($topic !== null)
		{
			return $topic->getDocumentService()->getParentOf($topic);
		}
		return null;
	}
	
	/**
	 * @return Integer
	 */
	public function getMountParentId()
	{
		$parent = $this->getMountParent();
		return ($parent === null) ? null : $parent->getId();
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $parent
	 */
	public function setMountParent($parent)
	{
		$this->parent = $parent;
		$this->setModificationdate(null);
	}
	
	/**
	 * @param Integer $parentId
	 */
	public function setMountParentId($parentId)
	{
		$parent = null;
		if ($parentId !== null)
		{
			$parent = DocumentHelper::getDocumentInstance($parentId);
		}
		$this->setMountParent($parent);
	}
	
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */	
	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
	{
	    if ($treeType == 'wlist')
		{
	    	$nodeAttributes['path'] = $this->getDocumentService()->getPathOf($this->getTopic());
		}
		$nodeAttributes['topicId'] = $this->getTopic()->getId();
	}
	
	/**
	 * @return website_persistentdocument_page
	 */
	public function getSiblingForumListPage()
	{
		try 
		{
			return TagService::getInstance()->getDocumentBySiblingTag('functional_forums_forum-list', $this->getTopic());
		}
		catch (Exception $e)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' ' . $e->getMessage());
			}
		}
		return null;
	}
	
	/**
	 * @return boolean
	 */
	public function mayContainThreads()
	{
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public function isLocked()
	{
		return $this->getLocked();
	}
		
	/**
	 * @return boolean
	 */
	public function isExcludedFromRss()
	{
		return $this->getExcludeFromRss();
	}
}