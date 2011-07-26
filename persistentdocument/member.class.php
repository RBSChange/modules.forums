<?php
/**
 * Class where to put your custom methods for document forums_persistentdocument_member
 * @package forums.persistentdocument
 */
class forums_persistentdocument_member extends forums_persistentdocument_memberbase implements indexer_IndexableDocument
{
	/**
	 * Get the indexable document
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocument()
	{
		$indexedDoc = new indexer_IndexedDocument();
		$indexedDoc->setId($this->getId());
		$indexedDoc->setDocumentModel('modules_forums/member');
		$indexedDoc->setLabel($this->getLabel());
		$indexedDoc->setLang($this->getLang());
		$indexedDoc->setText($this->getFullTextForIndexation());
		return $indexedDoc;
	}
	
	/**
	 * @return String
	 */
	protected function getFullTextForIndexation()
	{
		$fullText = '';
		if ($this->getCountry())
		{
			$fullText .= ' ' . $this->getCountry()->getLabel();
		}
		foreach ($this->getTitleArray() as $title)
		{
			$fullText .= ' ' . $title->getLabel();
		}
		$fullText .= ' ' . $this->getSignatureAsHtml();
		return f_util_StringUtils::htmlToText($fullText);
	}
	
	/**
	 * @return Boolean
	 */
	public function isBanned()
	{
		if ($this->getBan() !== null)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * @return String
	 */
	public function getMetadate()
	{
		return date_Formatter::format($this->getCreationdate(), 'd F Y');
	}
	
	/**
	 * @return Integer
	 */
	public function getNbpost()
	{
		return $this->getDocumentService()->getNbpostForUserId($this->getId());
	}
	
	/**
	 * @return Integer
	 */
	public function getNbthread()
	{
		return $this->getDocumentService()->getNbthreadForUserId($this->getId());
	}
	
	/**
	 * @return Integer
	 */
	public function getNbcomment()
	{
		return $this->getDocumentService()->getNbcommentForUserId($this->getUser()->getId());
	}
	
	/**
	 * @return Boolean
	 */
	public function isme()
	{
		$current = forums_MemberService::getInstance()->getCurrentMember();
		if ($current !== null)
		{
			return $this->getId() == $current->getId();
		}
		return false;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return Boolean
	 */
	public function isSuperModerator($website = null)
	{
		if ($website === null)
		{
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		}
		$folder = forums_WebsitefolderService::getInstance()->getByWebsite($website);
		return forums_ModuleService::getInstance()->hasPermissionOnId($this, 'modules_forums.Banuser', $folder->getId());
	}
	
	/**
	 * @retunr Boolean
	 */
	public function hasToShowBansToCurrentUser()
	{
		$currentMember = forums_MemberService::getInstance()->getCurrentMember();
		if ($currentMember !== null)
		{
			return $currentMember->isSuperModerator($this->getWebsite());
		}
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public function getBans()
	{
		return forums_BanService::getInstance()->getBansForUser($this);
	}
	
	/**
	 * @param Integer $size
	 * @param String $defaultImageUrl
	 * @param String $rating
	 * @return String
	 */
	public function getGravatarUrl($size = '32', $defaultImageUrl = '', $rating = 'g')
	{
		$url = 'http://www.gravatar.com/avatar/' . md5($this->getEmail()) . '?s=' . $size . '&amp;r=' . $rating;
		if ($defaultImageUrl)
		{
			$url .= '&amp;d=' . urlencode($defaultImageUrl);
		}
		return $url;
	}

	/**
	 * @return String
	 */
	public function getEmail()
	{
		return $this->getUser()->getEmail();
	}
	
	/**
	 * @return String
	 */
	public function getShortWebsiteUrl()
	{
		$shortUrl = $this->getWebsiteUrl();
		if (f_util_StringUtils::strlen($shortUrl) > 33)
		{
			$shortUrl = f_util_StringUtils::substr($shortUrl, 0, 13) . '.....' . f_util_StringUtils::substr($shortUrl, -13);
		}
		return $shortUrl;
	}
	
	/**
	 * @return Boolean
	 */
	public function isEditable()
	{
		$currentMember = forums_MemberService::getInstance()->getCurrentMember();
		return ($currentMember !== null && $currentMember->isSuperModerator($this->getWebsite()));
	}

	/**
	 * @return website_persistentdocument_website
	 */
	public function getWebsite()
	{
		if ($this->getUser())
		{
			return DocumentHelper::getDocumentInstance($this->getUser()->getWebsiteid());
		}
		return null;
	}
	
	/**
	 * @return forums_persistentdocument_rank
	 */
	public function getRank()
	{
		return forums_RankService::getInstance()->getByMember($this);
	}
	
	/**
	 * @return Array<forumId: Integer, lastReadDate: String> 
	 */
	public function getTrackingByForum()
	{
		$data = parent::getTrackingByForum();
		return $data !== null ? unserialize($data) : array();
	}
	
	/**
	 * @param Array<forumId: Integer, lastReadDate: String> $data
	 */
	public function setTrackingByForum($data)
	{
		if (is_array($data) && f_util_ArrayUtils::isNotEmpty($data))
		{
			parent::setTrackingByForum(serialize($data));
		}
		else 
		{
			parent::setTrackingByForum(null);
		}
	}
	
	/**
	 * @param Integer $forumId
	 * @return string
	 */
	public function getLastReadDateByForumId($forumId)
	{
		$track = $this->getTrackingByForum();
		if (isset($track[$forumId]) && $track[$forumId])
		{
			return $track[$forumId];
		}
		$member = forums_MemberService::getInstance()->getCurrentMember();
		return $member->getDocumentService()->getAllReadDate($member);
	}
	
	/**
	 * @return Array<threadId: Integer, lastReadDate: String> 
	 */
	public function getTrackingByThread()
	{
		$data = parent::getTrackingByThread();
		return $data !== null ? unserialize($data) : array();
	}
	
	/**
	 * @param Array<threadId: Integer, lastReadDate: String> $data
	 */
	public function setTrackingByThread($data)
	{
		if (is_array($data) && f_util_ArrayUtils::isNotEmpty($data))
		{
			parent::setTrackingByThread(serialize($data));
		}
		else 
		{
			parent::setTrackingByThread(null);
		}
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
	 * @param Integer $threadId
	 * @return string
	 */
	public function getLastReadDateByThreadId($threadId)
	{
		if (isset($this->tempLastReadDateByThreadId[$threadId]))
		{
			return $this->tempLastReadDateByThreadId[$threadId];
		}
		$track = $this->getTrackingByThread();
		if (isset($track[$threadId]) && $track[$threadId])
		{
			return $track[$threadId];
		}
		$member = forums_MemberService::getInstance()->getCurrentMember();
		return $member->getDocumentService()->getAllReadDate($member);
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
	
	/**
	 * @return String
	 */
	public function getSignatureAsHtml()
	{
		$parser = new website_BBCodeParser();
		return $parser->convertXmlToHtml($this->getSignature());
	}
	
	/**
	 * @return string
	 */
	public function getSignatureAsBBCode()
	{
		$parser = new website_BBCodeParser();
		return $parser->convertXmlToBBCode($this->getSignature());
	}

	/**
	 * @param string $bbcode
	 */
	public function setSignatureAsBBCode($bbcode)
	{
		$parser = new website_BBCodeParser();
		$this->setSignature($parser->convertBBCodeToXml($bbcode, $parser->getModuleProfile('forums')));
	}
}