<?php
/**
 * forums_persistentdocument_thread
 * @package modules.forums.persistentdocument
 */
class forums_persistentdocument_thread extends forums_persistentdocument_threadbase implements rss_Item
{
	/**
	 * @return String
	 */
	public function getForumLabel()
	{
		return $this->getForum()->getLabel();
	}
	
	/**
	 * @return boolean
	 */
	public function isVisible()
	{
		return $this->getForum()->isVisible();
	}
	
	/**
	 * @return Boolean
	 */
	public function isWriteable()
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		if (!$this->isVisible() || $user === null || forums_ModuleService::getInstance()->isBanned($user))
		{
			return false;
		}
		elseif ($this->isLocked())
		{
			return false;
		}
		return true;
	}
	
	/**
	 * @return Boolean
	 */
	public function isLocked()
	{
		if ($this->getLocked() || $this->getForum()->isLocked())
		{
			return true;
		}
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public function isEditable()
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		if (!$this->isVisible())
		{
			return false;
		}
		elseif (forums_ModuleService::getInstance()->hasPermission($user, 'modules_forums.Moderate', $this))
		{
			return true;
		}
		elseif ($user !== null && $this->getAuthorid() == $user->getId() && !$this->isLocked())
		{
			return true;
		}
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public function isClosable()
	{
		return (!$this->getLocked() && forums_ModuleService::getInstance()->currentUserHasPermission('modules_forums.Moderate', $this));
	}
	
	/**
	 * @return Boolean
	 */
	public function isOpenable()
	{
		return ($this->getLocked() && forums_ModuleService::getInstance()->currentUserHasPermission('modules_forums.Moderate', $this));
	}
	
	/**
	 * @return String
	 */
	public function getUserUrl()
	{
		return $this->getDocumentService()->getUserUrl($this);
	}
	
	/**
	 * @return Integer
	 */
	public function getNbnewpost()
	{
		$fps = forums_ForumsprofileService::getInstance();
		$user = users_UserService::getInstance()->getCurrentUser();
		$profile = ($user) ? $fps->getByAccessorId($user->getId()) : null;

		$last = null;
		if ($user !== null)
		{
			$last = $profile->getLastReadDateByThreadId($this->getId());
		}
		if ($last === null)
		{
			$last = $fps->getAllReadDate($profile);
		}
		return f_util_ArrayUtils::firstElement(forums_PostService::getInstance()->createQuery()
			->add(Restrictions::eq('thread', $this))
			->add(Restrictions::gt('creationdate', $last))
			->setProjection(Projections::rowCount('count'))
			->setFetchColumn('count')->find());
	}
	
	/**
	 * @return String
	 */
	public function getJsPrivateNote()
	{
		$txt = str_replace("'", "\\'", $this->getPrivatenoteAsBBCode());
		$txt = str_replace("\n", '\n', $txt);
		$txt = str_replace("\r", "", $txt);
		return f_util_HtmlUtils::textToHtml($txt);
	}
	
	/**
	 * @return Boolean
	 */
	public function canFollow()
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($user !== null && !in_array($user->getId(), DocumentHelper::getIdArrayFromDocumentArray($this->getFollowersArray())))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public function canUnfollow()
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($user !== null && in_array($user->getId(), DocumentHelper::getIdArrayFromDocumentArray($this->getFollowersArray())))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * @return boolean
	 */
	public function hasPages()
	{
		return $this->getNbpost() > $this->getForum()->getNbPostPerPage();
	}
	
	/**
	 * @return string[]
	 */
	public function getPagination()
	{
		$pageCount = ceil($this->getNbpost() / $this->getForum()->getNbPostPerPage());
		
		if ($pageCount < 2)
		{
			return null;
		}
		
		$pagination = array();
		if ($pageCount <= 5)
		{
			for ($page = 1; $page < $pageCount; $page++)
			{
				$pagination[] = '<a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => $page)) . '">' . $page . '</a><span>, </span>';
			}
		}
		else 
		{
			$pagination[] = '<a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => 1)) . '">1</a><span> ... </span>';
			for ($page = $pageCount - 2; $page < $pageCount; $page++)
			{
				$pagination[] = '<a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => $page)) . '">' . $page . '</a><span>, </span>';
			}
		}
		$pagination[] = '<a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => $pageCount)) . '">' . $pageCount . '</a>';
		return $pagination;
	}
	
	/**
	 * @return boolean
	 */
	public function canModerate()
	{
		return forums_ModuleService::getInstance()->currentUserHasPermission('modules_forums.Moderate', $this);
	}

	/**
	 * @return String
	 */
	public function getKeywords()
	{
		return str_replace(' ', ', ', $this->getLabel()).', '.LocaleService::getInstance()->transFO('m.forums.meta.forum');
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
	public function getRSSLabel()
	{
		return $this->getLabelAsHtml() . ' - ' . LocaleService::getInstance()->transFO('m.forums.frontoffice.in-forum', array('lab')) . ' ' . $this->getForum()->getLabelAsHtml();
	}
	
	/**
	 * @return String
	 */
	public function getRSSDescription()
	{
		$firstPost = $this->getFirstPost();
		if ($firstPost->getDeleteddate() === null)
		{ 
			return $firstPost->getTextAsHtml();	
		}
		return "";
	}
	
	/**
	 * @return String
	 */
	public function getRSSGuid()
	{
		return LinkHelper::getDocumentUrl($this);
	}
	
	/**
	 * @return String
	 */
	public function getRSSDate()
	{
		return $this->getCreationdate();
	}
	
	/**
	 * @return String
	 */
	public function getDescriptionAsHtml()
	{
		return $this->getFirstPost()->getTextAsHtml();
	}
	
	/**
	 * @return String
	 */
	public function getPostFeedLabel()
	{
		$ls = LocaleService::getInstance();
		return $ls->transFO('m.forums.frontoffice.posts-of', array('lab', 'ucf'), array('type' => $ls->transFO($this->getPersistentModel()->getLabelKey()))) . ' ' . $this->getLabel();
	}
}