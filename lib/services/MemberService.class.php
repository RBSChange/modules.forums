<?php
/**
 * forums_MemberService
 * @package forums
 */
class forums_MemberService extends f_persistentdocument_DocumentService
{
	/**
	 * @var forums_MemberService
	 */
	private static $instance;

	/**
	 * @return forums_MemberService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return forums_persistentdocument_member
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_forums/member');
	}

	/**
	 * Create a query based on 'modules_forums/member' model.
	 * Return document that are instance of modules_forums/member,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_forums/member');
	}
	
	/**
	 * Create a query based on 'modules_forums/member' model.
	 * Only documents that are strictly instance of modules_forums/member
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_forums/member', false);
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return forums_persistentdocument_member
	 */
	public function getPublishedMembersByWebsite($website)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::published());
		$query->createCriteria('user')
			->add(Restrictions::eq('groups', $website->getGroup()));
		$query->addOrder(Order::iasc('label'));
		return $query->find();
	}
	
	/**
	 * @return forums_persistentdocument_member
	 */
	public function getCurrentMember()
	{
		return $this->getByUser(users_UserService::getInstance()->getCurrentFrontEndUser());
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param Boolean $createIfNull
	 * @return forums_persistentdocument_member
	 */
	public function getByUser($user, $createIfNull = true)
	{
		if ($user instanceof users_persistentdocument_user)
		{
			$member = f_util_ArrayUtils::firstElement($user->getMemberArrayInverse());
			if ($member === null && $createIfNull)
			{
				$member = $this->getNewDocumentInstance();
				$member->setUser($user);
				$member->save();
			}
			return $member;
		}
		return null;
	}
	
	/**
	 * @param String $login
	 * @param Integer $websiteId
	 * @param Boolean $createMemberIfNull
	 * @return forums_persistentdocument_member
	 */
	public function getByLogin($login, $websiteId = null, $createMemberIfNull = true)
	{
		if ($websiteId === null)
		{
			$website = website_WebsiteService::getInstance()->getCurrentWebsite();
			$websiteId = $website->getId();
		}
		$user = users_UserService::getInstance()->getFrontendUserByLogin($login, $websiteId);
		if ($user !== null)
		{
			return $this->getByUser($user, $createMemberIfNull);
		}
		return null;
	}
	
	/**
	 * @param String $label
	 * @param Integer $websiteId
	 * @return forums_persistentdocument_member
	 */
	public function getByLabel($label, $websiteId = null)
	{
		if ($websiteId === null)
		{
			$website = website_WebsiteService::getInstance()->getCurrentWebsite();
		}
		else
		{
			$website = DocumentHelper::getDocumentInstance($websiteId);
		}
		$query = $this->createQuery()
			->add(Restrictions::eq('label', $label));
		$query->add(Restrictions::eq('user.groups', $website->getGroup()));
		return $query->findUnique(); 
	}
	
	/**
	 * @param Integer $userId
	 * @return Integer
	 */
	public function getNbpostForUserId($userId)
	{
		$row = forums_PostService::getInstance()->createQuery()->add(Restrictions::eq('postauthor', $userId))->setProjection(Projections::rowCount('nb'))->findUnique();
		return $row['nb'];
	}
	
	/**
	 * @param Integer $userId
	 * @return Integer
	 */
	public function getNbthreadForUserId($userId)
	{
		$row = forums_ThreadService::getInstance()->createQuery()->add(Restrictions::eq('threadauthor', $userId))->setProjection(Projections::rowCount('nb'))->findUnique();
		return $row['nb'];
	}

	/**
	 * @param Integer $userId
	 * @return Integer
	 */
	public function getNbcommentForUserId($userId)
	{
		$row = comment_CommentService::getInstance()->createQuery()->add(Restrictions::eq('authorid', $userId))->setProjection(Projections::rowCount('nb'))->findUnique();
		return $row['nb'];
	}
		
	/**
	 * @param forums_persistentdocument_member $member
	 * @param forums_persistentdocument_post $post
	 */
	public function setPostAsReadForMember($member, $post)
	{
		try
		{
			$this->tm->beginTransaction();
			
			$allReadDate = $this->getAllReadDate($member);
			
			// By thread...
			$track = $member->getTrackingByThread();
			$thread = $post->getThread();
			$threadId = $thread->getId();
			$postDate = $post->getCreationdate();
			if (!isset($track[$threadId]))
			{
				$member->setTempLastReadDateByThreadId($threadId, $allReadDate);
			}
			else if ($track[$threadId] < $postDate)
			{
				$member->setTempLastReadDateByThreadId($threadId, $track[$threadId]);
			}
			if ($postDate > $allReadDate)
			{
				if (!isset($track[$threadId]) || $track[$threadId] < $postDate)
				{
					$track[$threadId] = $postDate;
				}
			}
			else if (isset($track[$threadId]) && $track[$threadId] <= $allReadDate)
			{
				unset($track[$threadId]);
			}
			$member->setTrackingByThread($track);
			
			// By forum...
			$track = $member->getTrackingByForum();
			$forumId = $thread->getForum()->getId();
			if ($postDate > $allReadDate)
			{
				if (!isset($track[$forumId]) || $track[$forumId] < $postDate)
				{
					$track[$forumId] = $postDate;
				}
			}
			else if (isset($track[$forumId]) && $track[$forumId] <= $allReadDate)
			{
				unset($track[$forumId]);
			}
			$member->setTrackingByForum($track);
			
			$this->pp->updateDocument($member);
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}
	
	/**
	 * @param forums_persistentdocument_member $member
	 * @return String
	 */
	public function getAllReadDate($member = null)
	{
		if ($member !== null && $member->getLastAllRead() !== null)
		{
			return max($member->getLastAllRead(), forums_ModuleService::getInstance()->getGlobalAllReadDate());
		}
		return forums_ModuleService::getInstance()->getGlobalAllReadDate();
	}
	
	/**
	 * @param forums_persistentdocument_member $member
	 */
	public function cleanTracking($member)
	{
		try
		{
			$this->tm->beginTransaction();
			
			$date = $this->getAllReadDate($member);
		
			// By thread...
			$track = $member->getTrackingByThread();
			foreach ($track as $key => $value)
			{
				if ($date >= $value)
				{
					unset($track[$key]);
				}
			}
			$member->setTrackingByThread($track);
			
			// By forum...
			$track = $member->getTrackingByForum();
			foreach ($track as $key => $value)
			{
				if ($date >= $value)
				{
					unset($track[$key]);
				}
			}
			$member->setTrackingByForum($track);
			
			$this->pp->updateDocument($member);
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}
		
	/**
	 * @param forums_persistentdocument_member $member
	 */
	public function refreshLabel($member)
	{
		if ($member->getUser() !== null && $member->getLabel() === null)
		{
			$member->setLabel(($member->getDisplayname()) ? (f_util_StringUtils::ucfirst($member->getUser()->getFirstname()) . ' ' . f_util_StringUtils::ucfirst($member->getUser()->getLastname())) : $member->getUser()->getLogin());
		}
	}
	
	/**
	 * @param forums_persistentdocument_member $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);
		
		$this->refreshLabel($document);
	}

	/**
	 * @param forums_persistentdocument_member $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		parent::preInsert($document, $parentNodeId);
		
		$document->setInsertInTree(false);
	}
	
	/**
	 * @param forums_persistentdocument_member $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		return users_UserService::getInstance()->getWebsiteId($document->getUser());
	}
	
	/**
	 * @param forums_persistentdocument_member $document
	 * @return integer
	 */
	public function getWebsiteIds($document)
	{
		return users_UserService::getInstance()->getWebsiteIds($document->getUser());
	}
		
	/**
	 * @param forums_persistentdocument_member $document
	 * @param string $actionType
	 * @param array $formProperties
	 */
	public function addFormProperties($document, $propertiesNames, &$formProperties)
	{	
		$formProperties['websiteId'] = $document->getWebsite()->getId();
	}
	
	/**
	 * @param forums_persistentdocument_member $member
	 * @param string $deletePosts 'true'|'false'
	 */
	public function deleteDelayed($member, $deletePosts = false)
	{
		$tm = $this->getTransactionManager();
		try 
		{
			$tm->beginTransaction();
			$member->setMeta('deletePosts', $deletePosts);
			$member->saveMeta();
			$this->putInTrash($member->getId());
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
	}
	
	/**
	 * @return array
	 */
	public function getIdsToDelete()
	{
		return $this->createQuery()->add(Restrictions::eq('publicationstatus', 'TRASH'))
			->setProjection(Projections::property('id'))->findColumn('id');
	}
	
	/**
	 * @param forums_persistentdocument_member $member
	 * @param integer $max the maximum number of documents that can treat
	 * @return integer the maximum number of documents that can still treat
	 */
	public function prepareMemberDeletion($member, $max)
	{
		// Handle bans.
		$max -= forums_BanService::getInstance()->treatBansForMemberDeletion($member, $max);
		if ($max < 1)
		{
			return $max;
		}
		
		// Handle posts.
		$max -= forums_PostService::getInstance()->treatPostsForMemberDeletion($member, $max);
		if ($max < 1)
		{
			return $max;
		}
		
		// Handle threads.
		$max -= forums_ThreadService::getInstance()->treatThreadsForMemberDeletion($member, $max);
		if ($max < 1)
		{
			return $max;
		}
		
		// Handle privatemessaging.
		if (ModuleService::getInstance()->moduleExists('privatemessaging'))
		{
			$pmMember = privatemessaging_MemberService::getInstance()->getByUser($member->getUser());		
			$max -= $pmMember->getDocumentService()->deleteMember($pmMember, $max);
			if ($max < 1)
			{
				return $max;
			}
		}
		
		return $max;
	}
}