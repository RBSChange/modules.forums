<?php
/**
 * forums_persistentdocument_thread
 * @package modules.forums.persistentdocument
 */
class forums_persistentdocument_thread extends forums_persistentdocument_threadbase implements rss_Item
{
	/**
	 * Store the flag Label
	 * @var String
	 */
	private $flagLabel = null;
	
	/**
	 * @return string
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
	 * @return boolean
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
	 * @return boolean
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
	 * @return boolean
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
	 * @return boolean
	 */
	public function isClosable()
	{
		return (!$this->getLocked() && forums_ModuleService::getInstance()->currentUserHasPermission('modules_forums.Moderate', $this));
	}
	
	/**
	 * @return boolean
	 */
	public function isOpenable()
	{
		return ($this->getLocked() && forums_ModuleService::getInstance()->currentUserHasPermission('modules_forums.Moderate', $this));
	}
	
	/**
	 * @return string
	 */
	public function getUserUrl()
	{
		return $this->getDocumentService()->getUserUrl($this);
	}
	
	/**
	 * @return integer
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
	 * @return string
	 */
	public function getJsPrivateNote()
	{
		$txt = str_replace("'", "\\'", $this->getPrivatenoteAsBBCode());
		$txt = str_replace("\n", '\n', $txt);
		$txt = str_replace("\r", "", $txt);
		return f_util_HtmlUtils::textToHtml($txt);
	}
	
	/**
	 * @return boolean
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
	 * @return boolean
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
				$pagination[] = '<a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => $page)) . '">' . $page . '</a><span class="punctuation">, </span>';
			}
		}
		else 
		{
			$pagination[] = '<a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => 1)) . '">1</a><span class="separator"> ... </span>';
			for ($page = $pageCount - 2; $page < $pageCount; $page++)
			{
				$pagination[] = '<a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => $page)) . '">' . $page . '</a><span class="punctuation">, </span>';
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
	 * @return string
	 */
	public function getKeywords()
	{
		return str_replace(' ', ', ', $this->getLabel()).', '.LocaleService::getInstance()->trans('m.forums.meta.forum');
	}
	
	/**
	 * @return forums_persistentdocument_post
	 */
	public function getLastPost()
	{
		return $this->getDocumentService()->getLastPost($this);
	}
	
	/**
	 * @return string
	 */
	public function getRSSLabel()
	{
		return $this->getLabelAsHtml() . ' - ' . LocaleService::getInstance()->trans('m.forums.frontoffice.in-forum', array('lab')) . ' ' . $this->getForum()->getLabelAsHtml();
	}
	
	/**
	 * @return string
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
	 * @return string
	 */
	public function getRSSGuid()
	{
		return LinkHelper::getPermalink($this);
	}
	
	/**
	 * @return string
	 */
	public function getRSSLink()
	{
		return LinkHelper::getDocumentUrl($this);
	}
	
	/**
	 * @return string
	 */
	public function getRSSDate()
	{
		return $this->getCreationdate();
	}
	
	/**
	 * @return string
	 */
	public function getDescriptionAsHtml()
	{
		return $this->getFirstPost()->getTextAsHtml();
	}
	
	/**
	 * @return string
	 */
	public function getPostFeedLabel()
	{
		$ls = LocaleService::getInstance();
		return $ls->trans('m.forums.frontoffice.posts-of', array('lab', 'ucf'), array('type' => $ls->trans($this->getPersistentModel()->getLabelKey()))) . ' ' . $this->getLabel();
	}
	
	/**
	 * Get the flag label
	 * @return string
	 */
	public function getFlagLabel()
	{
		if ($this->flagLabel == null)
		{
			$this->flagLabel = $this->getDocumentService()->getFlagLabel($this);
		}
		return $this->flagLabel;
	}
}