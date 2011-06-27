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
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if (!$this->isVisible() || $member === null || $member->isBanned())
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
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if (!$this->isVisible())
		{
			return false;
		}
		if (forums_ModuleService::getInstance()->hasPermission($member, 'modules_forums.Moderate', $this))
		{
			return true;
		}
		else if ($member !== null && $this->getThreadauthor()->getId() == $member->getId() && !$this->isLocked())
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
		$member = forums_MemberService::getInstance()->getCurrentMember();
		return (!$this->getLocked() && forums_ModuleService::getInstance()->hasPermission($member, 'modules_forums.Moderate', $this));
	}
	
	/**
	 * @return Boolean
	 */
	public function isOpenable()
	{
		$member = forums_MemberService::getInstance()->getCurrentMember();
		return ($this->getLocked() && forums_ModuleService::getInstance()->hasPermission($member, 'modules_forums.Moderate', $this));
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
		$ms = forums_MemberService::getInstance();
		$member = forums_MemberService::getInstance()->getCurrentMember();
		$last = null;
		if ($member !== null)
		{
			$last = $member->getLastReadDateByThreadId($this->getId());
		}
		if ($last === null)
		{
			$last = $ms->getAllReadDate($member);
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
	private function getPrivatenotebyLabelAsHtml()
	{
		if ($this->getPrivatenoteby() !== null)
		{
			return $this->getPrivatenoteby()->getLabelAsHtml();
		}
		return '';
	}
	
	/**
	 * @return String
	 */
	public function getPrivatenoteAsHtml()
	{
		$parser = new website_BBCodeParser();
		return $parser->convertXmlToHtml($this->getPrivatenote());
	}

	/**
	 * @return string
	 */
	public function getPrivatenoteAsBBCode()
	{
		$parser = new website_BBCodeParser();
		return $parser->convertXmlToBBCode($this->getPrivatenote());
	}

	/**
	 * @param string $bbcode
	 */
	public function setPrivatenoteAsBBCode($bbcode)
	{
		$parser = new website_BBCodeParser();
		$this->setPrivatenote($parser->convertBBCodeToXml($bbcode, $parser->getModuleProfile('forums')));
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
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if ($member !== null && !in_array($member->getId(), DocumentHelper::getIdArrayFromDocumentArray($this->getFollowersArray())))
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
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if ($member !== null && in_array($member->getId(), DocumentHelper::getIdArrayFromDocumentArray($this->getFollowersArray())))
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
	 * @return string
	 */
	public function getPagination()
	{
		$pageCount = ceil($this->getNbpost() / $this->getForum()->getNbPostPerPage());
		
		if ($pageCount < 2)
		{
			return null;
		}
		
		$pagination = '<a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => 1)) . '">1</a>';
		if ($pageCount <= 5)
		{
			for ($page = 2; $page <= $pageCount; $page++)
			{
				$pagination .= ', <a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => $page)) . '">' . $page . '</a>';
			}
		}
		else 
		{
			$pagination .= ' ... <a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => ($pageCount - 3))) . '">' . ($pageCount - 3) . '</a>';
			for ($page = $pageCount - 2; $page <= $pageCount; $page++)
			{
				$pagination .= ', <a class="link" href="' . LinkHelper::getDocumentUrl($this, null, array('forumsParam[page]' => $page)) . '">' . $page . '</a>';
			}
		}
		return $pagination;
	}
	
	/**
	 * @return boolean
	 */
	public function canModerate()
	{
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if (forums_ModuleService::getInstance()->hasPermission($member, 'modules_forums.Moderate', $this))
		{
			return true;
		}
		return false;
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