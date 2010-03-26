<?php
/**
 * forums_persistentdocument_forum
 * @package modules.forums.persistentdocument
 */
class forums_persistentdocument_forum extends forums_persistentdocument_forumbase implements indexer_IndexableDocument
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
	 * @return Integer
	 */
	public function getNbnewpost()
	{
		$ms = forums_MemberService::getInstance();
		$member = forums_MemberService::getInstance()->getCurrentMember();
		$last = null;
		if ($member !== null)
		{
			$last = $member->getLastReadDateByForumId($this->getId());
		}
		if ($last === null)
		{
			$last = $ms->getAllReadDate($member);
		}
			
		$query = forums_PostService::getInstance()->createQuery()
			->add(Restrictions::gt('creationdate', $last))
			->setProjection(Projections::rowCount('count'))
			->setFetchColumn('count');
		$query->createCriteria('thread')->add(Restrictions::eq('forum', $this));
		return f_util_ArrayUtils::firstElement($query->find());
	}
	
	/**
	 * @return Boolean
	 */
	public function isWritable()
	{
		if ($this->isLocked())
		{
			return false;
		}
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if ($member === null || $member->isBanned())
		{
			return false;
		}
		return true;
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
		$this->setMountParentId(($parent !== null) ? $parent->getId() : null);
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
		$this->parent = $parent;
		$this->setModificationdate(null);
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
	}
	
	/**
	 * @return Boolean
	 */
	public function isLocked()
	{
		if ($this->getLocked())
		{
			return true;
		}
		return false;
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
	 * @return forums_persistentdocument_post
	 */
	public function getLastPost()
	{
		return $this->getDocumentService()->getLastPost($this);
	}
	
	/**
	 * @return String
	 */
	public function getPostFeedLabel()
	{
		return f_Locale::translate('&modules.forums.frontoffice.Posts-ofLabel;', array('type' => f_Locale::translate($this->getPersistentModel()->getLabel()))) . ' ' . $this->getLabel();
	}
	
	/**
	 * @return String
	 */
	public function getThreadFeedLabel()
	{
		return f_Locale::translate('&modules.forums.frontoffice.Threads-ofLabel;', array('type' => f_Locale::translate($this->getPersistentModel()->getLabel()))) . ' ' . $this->getLabel();
	}
}