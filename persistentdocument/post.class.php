<?php
/**
 * forums_persistentdocument_post
 * @package modules.forums.persistentdocument
 */
class forums_persistentdocument_post extends forums_persistentdocument_postbase implements indexer_IndexableDocument, rss_Item
{
	/**
	 * Get the indexable document
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocument()
	{
		if ($this->getDeleteddate() !== null)
		{
			return null;
		}
		$indexedDoc = new indexer_IndexedDocument();
		$indexedDoc->setId($this->getId());
		$indexedDoc->setDocumentModel('modules_forums/post');
		if ($this->isFirstPostInThread())
		{
			$indexedDoc->setLabel($this->getThreadLabel());	
		}
		else
		{
			$indexedDoc->setLabel($this->getLabel());	
		}
		$indexedDoc->setLang($this->getLang());
		$indexedDoc->setText($this->getFullTextForIndexation());
		return $indexedDoc;
	}
	
	/**
	 * @return String
	 */
	private function getFullTextForIndexation()
	{
		return f_util_StringUtils::htmlToText($this->getTextAsHtml());
	}
	
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
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if (!$this->isVisible())
		{
			return false;
		}
		else if (forums_ModuleService::getInstance()->hasPermission($member, 'modules_forums.Moderate', $this))
		{
			return true;
		}
		else if ($this->getThread()->isLocked())
		{
			return false;
		}
		else if ($member !== null && $this->getPostauthor() !== null && $this->getPostauthor()->getId() == $member->getId())
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
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if (!$this->isVisible())
		{
			return false;
		}
		else if (forums_ModuleService::getInstance()->hasPermission($member, 'modules_forums.Moderate', $this))
		{
			return true;
		}
		else if ($this->getThread()->isLocked())
		{
			return false;
		}
		// TODO dernier message seulement?
		else if ($member !== null && $this->getPostauthor()->getId() == $member->getId())
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
		$member = forums_MemberService::getInstance()->getCurrentMember();
		$target = $this->getPostauthor();
		if ($target === null || $target->isBanned() || $member === null || $member->isBanned() || $target->getId() == $member->getId())
		{
			return false;
		}
		
		$website = $target->getWebsite();
		if ($target->isSuperModerator($website))
		{
			return false;
		}
		else if ($member->isSuperModerator($website))
		{
			return true;
		}
		return false;
	}
		
	/**
	 * @return String
	 */
	public function getAuthorNameAsHtml()
	{
		$member = $this->getPostauthor();
		if ($member instanceof forums_persistentdocument_member)
		{
			return $member->getLabelAsHtml();
		}
		return LocaleService::getInstance()->transFO('m.forums.frontoffice.unknown', array('ucf'));
	}
	
	/**
	 * @return String
	 */
	public function getLastEdithorNameAsHtml()
	{
		$member = $this->getEditedby();
		if ($member instanceof forums_persistentdocument_member)
		{
			return $member->getLabelAsHtml();
		}
		return LocaleService::getInstance()->transFO('m.forums.frontoffice.unknown', array('ucf'));
	}
	
	/**
	 * @return String
	 */
	public function getSuppressorNameAsHtml()
	{
		$member = $this->getDeletedby();
		if ($member instanceof forums_persistentdocument_member)
		{
			return $member->getLabelAsHtml();
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
		
		$member = forums_MemberService::getInstance()->getCurrentMember();
		return !forums_ModuleService::getInstance()->hasPermission($member, 'modules_forums.Moderate', $this);
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
		$ms = forums_MemberService::getInstance();
		$member = $ms->getCurrentMember();
		$globalLast = $ms->getAllReadDate($member);
	
		// A post without a thread is always new (preview).
		if ($this->getThread() === null)
		{
			return 'new';
		}
		
		// New?
		$last = null;
		if ($member !== null)
		{
			$last = $member->getLastReadDateByThreadId($this->getThread()->getId());
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
		if ($member !== null)
		{
			$last = $member->getMeta('m.forums.lastSessionStart');
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
	public function getTextAsHtml()
	{
		$parser = new website_BBCodeParser();
		return $parser->convertXmlToHtml($this->getText());
	}

	/**
	 * @return string
	 */
	public function getTextAsBBCode()
	{
		$parser = new website_BBCodeParser();
		return $parser->convertXmlToBBCode($this->getText());
	}

	/**
	 * @param string $bbcode
	 */
	public function setTextAsBBCode($bbcode)
	{
		$parser = new website_BBCodeParser();
		$this->setText($parser->convertBBCodeToXml($bbcode, $parser->getModuleProfile('forums')));
	}
}