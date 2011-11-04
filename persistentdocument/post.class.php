<?php
/**
 * forums_persistentdocument_post
 * @package modules.forums.persistentdocument
 */
class forums_persistentdocument_post extends forums_persistentdocument_postbase implements rss_Item
{
	/**
	 * @return Boolean
	 */
	public function isFirstPostInThread()
	{
		return $this->getNumber() == 1;
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
	public function isEditable()
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		if (!$this->isVisible())
		{
			return false;
		}
		else if (forums_ModuleService::getInstance()->hasPermission($user, 'modules_forums.Moderate', $this))
		{
			return true;
		}
		else if ($this->getThread()->isLocked())
		{
			return false;
		}
		else if ($user !== null && !forums_ModuleService::getInstance()->isBanned($user) && $this->getAuthorid() == $user->getId())
		{
			return true;
		}
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public function isDeletable()
	{
	$user = users_UserService::getInstance()->getCurrentUser();
		if (!$this->isVisible())
		{
			return false;
		}
		else if (forums_ModuleService::getInstance()->hasPermission($user, 'modules_forums.Moderate', $this))
		{
			return true;
		}
		else if ($this->getThread()->isLocked())
		{
			return false;
		}
		// TODO dernier message seulement?
		else if ($user !== null && !forums_ModuleService::getInstance()->isBanned($user) && $this->getAuthorid() == $user->getId())
		{
			return true;
		}
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public function isBanable()
	{
		$fms = forums_ModuleService::getInstance();
		$user = users_UserService::getInstance()->getCurrentUser();
		$author = $this->getAuthoridInstance();
		if ($user === null || $fms->isBanned($user) || $author === null || $fms->isBanned($author) || $author->getId() == $user->getId())
		{
			return false;
		}
		
		if ($fms->isSuperModerator($author))
		{
			return false;
		}
		else if ($fms->isSuperModerator($user))
		{
			return true;
		}
		return false;
	}

	/**
	 * @return String
	 */
	public function getAuthorProfile()
	{
		$user = $this->getAuthoridInstance();
		if ($user instanceof users_persistentdocument_user)
		{
			return $user->getProfile('forums');
		}
		return null;
	}
	
	/**
	 * @return String
	 */
	public function getAuthorName()
	{
		$user = $this->getAuthoridInstance();
		if ($user instanceof users_persistentdocument_user)
		{
			return $user->getLabel();
		}
		return LocaleService::getInstance()->transFO('m.forums.frontoffice.unknown', array('ucf'));
	}
	
	/**
	 * @return String
	 */
	public function getLastEdithorName()
	{
		$user = $this->getEditedbyInstance();
		if ($user instanceof users_persistentdocument_user)
		{
			return $user->getLabel();
		}
		return LocaleService::getInstance()->transFO('m.forums.frontoffice.unknown', array('ucf'));
	}
	
	/**
	 * @return String
	 */
	public function getSuppressorName()
	{
		$user = $this->getDeletedbyInstance();
		if ($user instanceof users_persistentdocument_user)
		{
			return $user->getLabel();
		}
		return LocaleService::getInstance()->transFO('m.forums.frontoffice.unknown', array('ucf'));
	}
	
	/**
	 * @return Boolean
	 */
	public function wasDeleted()
	{
		if ($this->getDeleteddate() === null)
		{
			return false;
		}
		return !forums_ModuleService::getInstance()->currentUserHasPermission('modules_forums.Moderate', $this);
	}
		
	/**
	 * @return Boolean
	 */
	public function isAnswer()
	{
		return ($this->getAnswerof() !== null);
	}

	/**
	 * @return String
	 */
	public function getForumLabel()
	{
		return $this->getThread()->getForum()->getLabel();
	}
	
	/**
	 * @return String
	 */
	public function getForumLabelAsHtml()
	{
		return $this->getThread()->getForum()->getLabelAsHtml();
	}
	
	/**
	 * @return String
	 */
	public function getThreadLabel()
	{
		return $this->getThread()->getLabel();
	}
	
	/**
	 * @return String
	 */
	public function getThreadLabelAsHtml()
	{
		return $this->getThread()->getLabelAsHtml();
	}
	
	/**
	 * @return String
	 */
	public function getPostId()
	{
		return $this->getThread()->getId() . '.' . $this->getNumber();
	}
	
	/**
	 * @return String
	 */
	public function getPostUrlInThread()
	{
		$postNumber = $this->getNumber();
		$thread = $this->getThread();
		if ($thread instanceof forums_persistentdocument_thread)
		{
			$pageNumber = ceil($postNumber / $thread->getForum()->getNbPostPerPage());
			$parameters = array();
			if ($pageNumber > 0)
			{
				$parameters = array('forumsParam[page]' => $pageNumber);
			}
			$link = LinkHelper::getDocumentUrl($thread, null, $parameters) . "#post-" . $this->getId();
			return $link;
		}
		return null;
	}
	
	/**
	 * @return String
	 */
	public function getPostIdLink($strong = false)
	{
		$link = '<a class="link" href="' . $this->getPostUrlInThread() . '">';
		if ($strong == true)
		{
			$link .= '<strong>';
		}
		$link .= $this->getPostId();
		if ($strong == true)
		{
			$link .= '</strong>';
		}
		$link .= '</a>';
		return $link;
	}
	
	/**
	 * @return forums_persistentdocument_forum
	 */
	public function getForum()
	{
		$thread = $this->getThread();
		return ($thread !== null) ? $thread->getForum() : null;
	}
	
	/**
	 * @param Integer $postPerPage
	 * @return Integer
	 */
	public function getPageNumberInThread($postPerPage)
	{
		return ceil($this->getNumber() / $postPerPage);
	}
	
	/**
	 * @return boolean
	 */
	public function isPostNew()
	{
		return $this->getPostNewStatus() == 'new';
	}

	/**
	 * @return boolean
	 */
	public function isPostSemiNew()
	{
		return $this->getPostNewStatus() == 'seminew';
	}

	/**
	 * @return boolean
	 */
	public function isPostOld()
	{
		return $this->getPostNewStatus() == 'old';
	}
	
	/**
	 * @return var new|seminew|old
	 */
	private $newStatus;
	
	/**
	 * @return string new|seminew|old
	 */
	private function getPostNewStatus()
	{
		if ($this->newStatus === null)
		{
			$this->newStatus = $this->calculatePostNewStatus();
		}
		return $this->newStatus;
	}

	/**
	 * @return string new|seminew|old
	 */
	private function calculatePostNewStatus()
	{
		$fps = forums_ForumsprofileService::getInstance();
		$user = users_UserService::getInstance()->getCurrentUser();
		$profile = ($user) ? $fps->getByAccessorId($user->getId()) : null;
		$globalLast = $fps->getAllReadDate($profile);
	
		// A post without a thread is always new (preview).
		if ($this->getThread() === null)
		{
			return 'new';
		}
		
		// New?
		$last = null;
		if ($profile !== null)
		{
			$last = $profile->getLastReadDateByThreadId($this->getThread()->getId());
		}
		if ($last === null)
		{
			$last = $globalLast;
		}
		if ($this->getCreationdate() > $last)
		{
			return 'new';
		}
		
		// Semi-new?
		$last = null;
		if ($user !== null)
		{
			$last = $user->getMeta('m.forums.lastSessionStart');
		}
		if ($last === null)
		{
			$last = $globalLast;
		}
		if ($this->getCreationdate() > $last)
		{
			return 'seminew';
		}
		
		// Old.
		return 'old';
	}
		
	/**
	 * @return String
	 */
	public function getRSSLabel()
	{
		return $this->getLabel() . ' - ' . LocaleService::getInstance()->transFO('m.forums.frontoffice.in-thread', array('lab')) . ' ' . $this->getThread()->getLabel();
	}
	
	/**
	 * @return String
	 */
	public function getRSSDescription()
	{
		return $this->getTextAsHtml();
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
	public function getAnchor()
	{
		return 'post-'.$this->getId();
	}
	
	// Deprecated.
	
	/**
	 * @deprecated use getAuthorName
	 */
	public function getAuthorNameAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getAuthorName());
	}
	
	/**
	 * @deprecated use getLastEdithorName
	 */
	public function getLastEdithorNameAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getLastEdithorName());
	}
	
	/**
	 * @deprecated use getSuppressorName
	 */
	public function getSuppressorNameAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getSuppressorName());
	}
}