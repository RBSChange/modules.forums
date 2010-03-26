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
	 * @return Boolean
	 */
	public function isWriteable()
	{
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if ($member === null || $member->isBanned())
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
	public function displayPrivatenote()
	{
		return website_BBCodeService::getInstance()->toHtml($this->getPrivatenote()) . '<br /><br />' . f_Locale::translate('&modules.forums.frontoffice.By;') . ' ' . $this->getPrivatenotebyLabelAsHtml();
	}
	
	/**
	 * @return String
	 */
	public function getJsPrivateNote()
	{
		$txt = str_replace("'", "\'", $this->getPrivatenoteAsHtml());
		$txt = str_replace("\n", '\n', $txt);
		$txt = str_replace("\r", "", $txt);
		return $txt;
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
	 * @return String
	 */
	public function getKeywords()
	{
		return str_replace(' ', ', ', $this->getLabel()).', '.f_Locale::translate('&modules.forums.meta.forum;');
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
		return $this->getLabelAsHtml() . ' - ' . f_Locale::translate('&modules.forums.frontoffice.in-forumLabel;') . ' ' . $this->getForum()->getLabelAsHtml();
	}
	
	/**
	 * @return String
	 */
	public function getRSSDescription()
	{
		return $this->getFirstPost()->getTextAsHtml();
	}
	
	/**
	 * @return String
	 */
	public function getRSSGuid()
	{
		return LinkHelper::getUrl($this);
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
		return f_Locale::translate('&modules.forums.frontoffice.Posts-ofLabel;', array('type' => f_Locale::translate($this->getPersistentModel()->getLabel()))) . ' ' . $this->getLabel();
	}
}