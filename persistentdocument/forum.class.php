<?php
/**
 * forums_persistentdocument_forum
 * @package modules.forums.persistentdocument
 */
class forums_persistentdocument_forum extends forums_persistentdocument_forumbase
{
	/**
	 * @return Integer
	 */
	public function getNbnewpost()
	{
		$fps = forums_ForumsprofileService::getInstance();
		$user = users_UserService::getInstance()->getCurrentUser();
		$profile = ($user) ? $fps->getByAccessorId($user->getId()) : null;
		
		$last = null;
		if ($profile !== null)
		{
			$last = $profile->getLastReadDateByForumId($this->getId());
		}
		if ($last === null)
		{
			$last = $fps->getAllReadDate($profile);
		}			
		$query = forums_PostService::getInstance()->createQuery()
			->add(Restrictions::gt('creationdate', $last))
			->setProjection(Projections::rowCount('count'));
		$query->createCriteria('thread')->add(Restrictions::eq('forum', $this));
		return f_util_ArrayUtils::firstElement($query->findColumn('count'));
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
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($user === null || forums_ModuleService::getInstance()->isBanned($user))
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