<?php
/**
 * Class where to put your custom methods for document forums_persistentdocument_forumsprofile
 * @package modules.forums.persistentdocument
 */
class forums_persistentdocument_forumsprofile extends forums_persistentdocument_forumsprofilebase
{
	/**
	 * @return boolean
	 */
	public function isBanned()
	{
		return forums_ModuleService::getInstance()->isBanned($this->getAccessorIdInstance());
	}
	
	/**
	 * @return integer
	 */
	public function getNbpost()
	{
		return forums_PostService::getInstance()->getCountByAuthorid($this->getAccessorId());
	}
	
	/**
	 * @return integer
	 */
	public function getNbthread()
	{
		return forums_ThreadService::getInstance()->getCountByAuthorid($this->getAccessorId());
	}
	
	/**
	 * @return integer
	 */
	public function getNbcomment()
	{
		return comment_CommentService::getInstance()->getCountByAuthorid($this->getAccessorId());
	}
	
	/**
	 * @return boolean
	 */
	public function isme()
	{
		$current = users_UserService::getInstance()->getCurrentUser();
		if ($current !== null)
		{
			return $this->getAccessorId() == $current->getId();
		}
		return false;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return boolean
	 */
	public function isSuperModerator($website = null)
	{
		$user = $this->getAccessorIdInstance();
		return forums_ModuleService::getInstance()->isSuperModerator($user, $website);
	}
	
	/**
	 * @retunr boolean
	 */
	public function hasToShowBansToCurrentUser()
	{
		$currentUser = users_UserService::getInstance()->getCurrentUser();
		return ($currentUser !== null && forums_ModuleService::getInstance()->isSuperModerator($currentUser));
	}
	
	/**
	 * @return boolean
	 */
	public function getBans()
	{
		return forums_BanService::getInstance()->getBansForUser($this);
	}
	
	/**
	 * @return boolean
	 */
	public function isEditable()
	{
		$currentUser = users_UserService::getInstance()->getCurrentUser();
		return ($currentUser !== null && forums_ModuleService::getInstance()->isSuperModerator($currentUser));
	}
	
	/**
	 * @return forums_persistentdocument_rank
	 */
	public function getRank()
	{
		return forums_RankService::getInstance()->getByUser($this->getAccessorIdInstance());
	}
	
	/**
	 * @param integer $forumId
	 * @return string
	 */
	public function getLastReadDateByForumId($forumId)
	{
		$track = $this->getDecodedTrackingByForum();
		if (isset($track[$forumId]) && $track[$forumId])
		{
			return $track[$forumId];
		}
		return $this->getDocumentService()->getAllReadDate($this);
	}
	
	/**
	 * @return void
	 */
	public function markAllPostsAsRead()
	{
		$this->setLastAllRead(date_Calendar::getInstance()->toString());
		$this->setTrackingByThread(null);
		$this->setTrackingByForum(null);
		$this->save();
	}
	
	/**
	 * @param integer $threadId
	 * @return string
	 */
	public function getLastReadDateByThreadId($threadId)
	{
		if (isset($this->tempLastReadDateByThreadId[$threadId]))
		{
			return $this->tempLastReadDateByThreadId[$threadId];
		}
		$track = $this->getDecodedTrackingByThread();
		if (isset($track[$threadId]) && $track[$threadId])
		{
			return $track[$threadId];
		}
		return $this->getDocumentService()->getAllReadDate($this);
	}
	
	/**
	 * @var array
	 */
	private $tempLastReadDateByThreadId = array();
	
	/**
	 * @param integer $threadId
	 * @param string $date
	 */
	public function setTempLastReadDateByThreadId($threadId, $date)
	{
		$this->tempLastReadDateByThreadId[$threadId] = $date;
	}
}