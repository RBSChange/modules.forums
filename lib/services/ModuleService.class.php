<?php
/**
 * @package modules.forums
 * @method forums_ModuleService getInstance()
 */
class forums_ModuleService extends ModuleBaseService
{
	const EXTBOOL_FALSE = 0;
	const EXTBOOL_TRUE = 1;
	const EXTBOOL_INHERIT = 2;
	
	/**
	 * @param string $permission
	 * @param f_peristentdocument_PersistentDocument $document
	 * @return boolean
	 */
	public function currentUserHasPermission($permission, $document)
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		return $this->hasPermission($user, $permission, $document);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument or null
	 */
	public function getVirtualParentForBackoffice($document)
	{
		if ($document instanceof forums_persistentdocument_thread)
		{
			return $document->getForum();
		}
		return null;
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param string $permission
	 * @param f_peristentdocument_PersistentDocument $document
	 * @return boolean
	 */
	public function hasPermission($user, $permission, $document)
	{
		if ($document === null || $user === null || $this->isBanned($user))
		{
			return false;
		}
		return change_PermissionService::getInstance()->hasPermission($user, $permission, $document->getId(), false);
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param website_persistentdocument_website $website
	 * @return boolean
	 */
	public function isSuperModerator($user, $website = null)
	{
		if ($website === null)
		{
			$website = website_WebsiteService::getInstance()->getCurrentWebsite();
		}
		$folder = forums_WebsitefolderService::getInstance()->getByWebsite($website);
		return $this->hasPermission($user, 'modules_forums.Banuser', $folder);
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param website_persistentdocument_website $website
	 * @return boolean
	 */
	public function isBanned($user)
	{
		$profile = $user->getProfile('forums');
		return ($profile && $profile->getBan() !== null);
	}
	
	/**
	 * @return string
	 */
	public function getGlobalAllReadDate()
	{
		return date_Calendar::getInstance()->sub(date_Calendar::MONTH, 1)->toString();
	}

	/**
	 * @param integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function getParentNodeForPermissions($documentId)
	{
		$document = DocumentHelper::getDocumentInstance($documentId);
		if ($document instanceof forums_persistentdocument_thread || $document instanceof forums_persistentdocument_post)
		{
			$forum = $document->getForum();
			if ($forum !== null)
			{
				return TreeService::getInstance()->getInstanceByDocumentId($forum->getId());
			}
		}
		return null;
	}
	
	/**
	 * @return integer
	 */
	public function getRssMaxItemCount()
	{
		return 30;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return Array<String, Double>
	 */
	public function getDashboardGlobalStatisticsByWebsite($website)
	{
		return array(
			'forums' => $this->findProjectedTotal($website, 'website.id', forums_ForumService::getInstance()),
			'threads' => $this->findProjectedTotal($website, 'forum.website.id', forums_ThreadService::getInstance()),
			'posts' => $this->findProjectedTotal($website, 'thread.forum.website.id', forums_PostService::getInstance()),
			'users' => $this->findProjectedTotal($website->getGroup(), 'groups', users_UserService::getInstance())
		);
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param date_Calendar $fromDate
	 * @param date_Calendar $toDate
	 * @return Array<String, Double>
	 */
	public function getDashboardMonthStatisticsByWebsite($website, $fromDate, $toDate)
	{
		$us = users_UserService::getInstance();
		$group = $website->getGroup();
		return array(
			'monthLabel' => ucfirst(date_Formatter::format($fromDate, 'F Y')),
			'monthShortLabel' => date_Formatter::format($fromDate, 'm/Y'),
			'forums' => $this->findProjectedTotal($website, 'website.id', forums_ForumService::getInstance(), $fromDate, $toDate, 'creationdate'),
			'threads' => $this->findProjectedTotal($website, 'forum.website.id', forums_ThreadService::getInstance(), $fromDate, $toDate, 'creationdate'),
			'posts' => $this->findProjectedTotal($website, 'thread.forum.website.id', forums_PostService::getInstance(), $fromDate, $toDate, 'creationdate'),
			'users' => $this->findProjectedTotal($group, 'groups', $us, $fromDate, $toDate, 'creationdate'),
			'lastlogin' => $this->findProjectedTotal($group, 'groups', $us, $fromDate, $toDate, 'lastlogin'),
			'hasposted' => $this->findHasPostedTotal($website, $fromDate, $toDate, 'creationdate')
		);
	}
	
	/**
	 * @param f_persitentdocument_PersistentDocument $reference
	 * @param string $referenceField
	 * @param DocumentService $service
	 * @param date_Calendar $fromDate
	 * @param date_Calendar $toDate
	 * @param date_Calendar $dateToCompare
	 * @return Mixed
	 */
	private function findProjectedTotal($reference, $referenceField, $service, $fromDate = null, $toDate = null, $dateToCompare = null)
	{
		$query = $service->createQuery();
		if ($fromDate && $toDate)
		{
			$query->add(Restrictions::between(
				$dateToCompare,
				$fromDate->toString(),
				$toDate->toString()
			));
		}
		$query->add(Restrictions::eq($referenceField, $reference->getId()));
		return f_util_ArrayUtils::firstElement($query->setProjection(Projections::rowCount('projection'))->findColumn('projection'));
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param date_Calendar $fromDate
	 * @param date_Calendar $toDate
	 * @param date_Calendar $dateToCompare
	 * @return Mixed
	 */
	private function findHasPostedTotal($website, $fromDate = null, $toDate = null, $dateToCompare = null)
	{
		$query = forums_PostService::getInstance()->createQuery();
		if ($fromDate && $toDate)
		{
			$query->add(Restrictions::between(
				$dateToCompare,
				$fromDate->toString(),
				$toDate->toString()
			));
		}
		$query->createCriteria('thread')->createCriteria('forum')->add(Restrictions::eq('website', $website));
		return f_util_ArrayUtils::firstElement($query->setProjection(Projections::distinctCount('authorid', 'projection'))->findColumn('projection'));
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param array $attributes
	 * @param string $script
	 * @return array
	 */
	public function getStructureInitializationAttributes($container, $attributes, $script)
	{
		switch ($script)
		{
			case 'forumgroupDefaultStructure':
				return $this->getForumgroupStructureInitializationAttributes($container, $attributes, $script);
				
			case 'membersDefaultStructure' :
				return $this->getMembersStructureInitializationAttributes($container, $attributes, $script);
			
			default:
				throw new BaseException('Unknown structure initialization script: '.$script, 'm.website.bo.actions.unknown-structure-initialization-script', array('script' => $script));
		}
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param array $attributes
	 * @param string $script
	 * @return array
	 */
	public function getForumgroupStructureInitializationAttributes($container, $attributes, $script)
	{
		// Check container.
		if (!$container instanceof forums_persistentdocument_forumgroup)
		{
			throw new BaseException('Invalid forum group', 'modules.forums.bo.actions.Invalid-forumgroup');
		}
		
		$node = TreeService::getInstance()->getInstanceByDocument($container->getTopic());
		if (count($node->getChildren('modules_website/page')) > 0)
		{
			throw new BaseException('This forum group already contains pages', 'modules.forums.bo.actions.Forumgroup-already-contains-pages');
		}
		
		// Set atrtibutes.
		$attributes['byDocumentId'] = $container->getTopic()->getId();
		return $attributes;
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param array $attributes
	 * @param string $script
	 * @return array
	 */
	public function getMembersStructureInitializationAttributes($container, $attributes, $script)
	{
		// Check container.
		if (!$container instanceof forums_persistentdocument_websitefolder)
		{
			throw new BaseException('Invalid website folder', 'modules.forums.bo.actions.Invalid-websitefolder');
		}
		
		$ts = TagService::getInstance();
		$website = $container->getWebsite();
		if ($ts->hasDocumentByContextualTag('contextual_website_website_modules_forums_memberban', $website))
		{
			throw new BaseException('This website already contains specific member pages', 'modules.forums.bo.actions.Website-already-contains-member-page');
		}
		
		$page = $ts->getDocumentByContextualTag('contextual_website_website_modules_users_userslist', $website, false);
		if (!($page instanceof website_persistentdocument_page))
		{
			throw new BaseException('This website doesn\'t have a member topic. You can initialize it in users module.', 'modules.forums.bo.actions.Website-do-contains-member-topic');
		}
		
		// Set atrtibutes.
		$attributes['byDocumentId'] = $page->getTopic()->getId();
		return $attributes;
	}

	/**
	 * @param users_persistentdocument_user $user
	 * @param integer $max the maximum number of documents that can treat
	 * @return integer the maximum number of documents that can still treat
	 */
	public function prepareUserDeletion($user, $max)
	{
		// Handle bans.
		$max -= forums_BanService::getInstance()->treatBansForUserDeletion($user, $max);
		if ($max < 1)
		{
			return $max;
		}
		
		// Handle threads.
		$max -= forums_ThreadService::getInstance()->treatThreadsForUserDeletion($user, $max);
		return $max;
	}
	
	// Deprecated
	
	/**
	 * @deprecated use hasPermission or currentUserHasPermission
	 */
	public function hasPermissionOnId($user, $permission, $documentId)
	{
		if ($documentId === null || $user === null || $this->isBanned($user))
		{
			return false;
		}
		return change_PermissionService::getInstance()->hasPermission($user, $permission, $documentId, false);
	}
}