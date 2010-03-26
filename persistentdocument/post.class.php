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
		$indexedDoc = new indexer_IndexedDocument();
		$indexedDoc->setId($this->getId());
		$indexedDoc->setDocumentModel('modules_forums/post');
		$indexedDoc->setLabel($this->getLabel());
		$indexedDoc->setLang($this->getLang());
		$indexedDoc->setText($this->getFullTextForIndexation());
		return $indexedDoc;
	}
	
	/**
	 * @return String
	 */
	private function getFullTextForIndexation()
	{
		return website_BBCodeService::getInstance()->toText($this->getTextAsHtml());
	}
	
	/**
	 * @return Boolean
	 */
	public function isFirstPostInThread()
	{
		return $this->getNumber() == 1;
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
		if (forums_ModuleService::getInstance()->hasPermission($member, 'modules_forums.Moderate', $this))
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
		return f_Locale::translate('&modules.forums.frontoffice.Unknown;');
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
		return f_Locale::translate('&modules.forums.frontoffice.Unknown;');
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
		return f_Locale::translate('&modules.forums.frontoffice.Unknown;');
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
	 * @return String
	 */
	public function getTextAsHtml()
	{
		return website_BBCodeService::getInstance()->toHtml($this->getText());
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
	 * @return String
	 */
	public function getRSSLabel()
	{
		return $this->getLabel() . ' - ' . f_Locale::translate('&modules.forums.frontoffice.in-threadLabel;') . ' ' . $this->getThread()->getLabel();
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
		return LinkHelper::getUrl($this);
	}
	
	/**
	 * @return String
	 */
	public function getRSSDate()
	{
		return $this->getCreationdate();
	}
}