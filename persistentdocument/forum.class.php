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
		$indexedDoc = parent::getIndexedDocument();
		$indexedDoc->setDocumentModel('modules_forums/forum');
		return $indexedDoc;
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
	 * @return forums_persistentdocument_post
	 */
	public function getLastPost()
	{
		return $this->getDocumentService()->getLastPost($this);
	}
	
	/**
	 * @return boolean
	 */
	public function mayContainThreads()
	{
		return true;
	}
}