<?php
/**
 * @package modules.forums.lib.services
 */
class forums_ModuleService extends ModuleBaseService
{
	const EXTBOOL_FALSE = 0;
	const EXTBOOL_TRUE = 1;
	const EXTBOOL_INHERIT = 2;
	
	/**
	 * Singleton
	 * @var forums_ModuleService
	 */
	private static $instance = null;
	
	/**
	 * @return forums_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
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
	 * @param forums_persistentdocument_member $member
	 * @param String $permission
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return Boolean
	 */
	public function hasPermission($member, $permission, $document)
	{
		if ($document === null || $member === null || $member->isBanned())
		{
			return false;
		}
		
		$ps = f_permission_PermissionService::getInstance();
		return $ps->hasExplicitPermission($member->getUser(), $permission, $document->getId());
	}
	
	/**
	 * @param forums_persistentdocument_member $member
	 * @param String $permission
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return Boolean
	 */
	public function hasPermissionOnId($member, $permission, $documentId)
	{
		if ($documentId === null || $member === null || $member->isBanned())
		{
			return false;
		}
		
		$ps = f_permission_PermissionService::getInstance();
		return $ps->hasExplicitPermission($member->getUser(), $permission, $documentId);
	}
	
	/**
	 * @return String
	 */
	public function getGlobalAllReadDate()
	{
		return date_Calendar::getInstance()->sub(date_Calendar::MONTH, 1)->toString();
	}

	/**
	 * @param Integer $documentId
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
	 * @return Integer
	 */
	public function getRssMaxItemCount()
	{
		return 30;
		// TODO?
		// ModuleService::getInstance()->getPreferenceValue('forums', 'rssMaxItemCount');
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
			'members' => $this->findProjectedTotal($website, 'user.websiteid', forums_MemberService::getInstance())
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
		$ms = forums_MemberService::getInstance();
		return array(
			'monthLabel' => ucfirst(date_DateFormat::format($fromDate, 'F Y')),
			'monthShortLabel' => date_DateFormat::format($fromDate, 'm/Y'),
			'forums' => $this->findProjectedTotal($website, 'website.id', forums_ForumService::getInstance(), $fromDate, $toDate, 'creationdate'),
			'threads' => $this->findProjectedTotal($website, 'forum.website.id', forums_ThreadService::getInstance(), $fromDate, $toDate, 'creationdate'),
			'posts' => $this->findProjectedTotal($website, 'thread.forum.website.id', forums_PostService::getInstance(), $fromDate, $toDate, 'creationdate'),
			'members' => $this->findProjectedTotal($website, 'user.websiteid', $ms, $fromDate, $toDate, 'creationdate'),
			'lastlogin' => $this->findProjectedTotal($website, 'user.websiteid', $ms, $fromDate, $toDate, 'user.lastlogin'),
			'hasposted' => $this->findProjectedTotal($website, 'user.websiteid', $ms, $fromDate, $toDate, 'post.creationdate')
		);
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param date_Calendar $fromDate
	 * @param date_Calendar $toDate
	 * @param f_persistentdocument_criteria_OperationProjection $projection
	 * @param String $orderStatus
	 * @return Mixed
	 */
	private function findProjectedTotal($website, $websiteField, $service, $fromDate = null, $toDate = null, $dateToCompare = null)
	{
		$dbFormat = 'Y-m-d H:i:s';
		$query = $service->createQuery();
		if ($fromDate && $toDate)
		{
			$query->add(Restrictions::between(
				$dateToCompare,
				date_DateFormat::format($fromDate, $dbFormat),
				date_DateFormat::format($toDate, $dbFormat)
			));
		}
		$query->add(Restrictions::eq($websiteField, $website->getId()));
		return f_util_ArrayUtils::firstElement($query->setProjection(Projections::rowCount('projection'))->findColumn('projection'));
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
				throw new BaseException('Unknown structure initialization script: '.$script, 'modules.brand.bo.actions.Unknown-structure-initialization-script', array('script' => $script));
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
		
		$website = $container->getWebsite();
		if (TagService::getInstance()->hasDocumentByContextualTag('contextual_website_website_modules_forums_memberlist', $website) || 
			TagService::getInstance()->hasDocumentByContextualTag('contextual_website_website_modules_forums_member', $website) ||
			TagService::getInstance()->hasDocumentByContextualTag('contextual_website_website_modules_forums_editprofile', $website) ||
			TagService::getInstance()->hasDocumentByContextualTag('contextual_website_website_modules_forums_memberban', $website))
		{
			throw new BaseException('This website already contains member pages', 'modules.forums.bo.actions.Website-already-contains-member-page');
		}
		
		// Set atrtibutes.
		$attributes['byDocumentId'] = $website->getId();
		return $attributes;
	}
	
	// Deprecated.
	
	/**
	 * @deprecated (will be removed in 4.0) use RequestContext::getClientIp()
	 */
	public function getIp()
	{
		return RequestContext::getInstance()->getClientIp();
	}
}