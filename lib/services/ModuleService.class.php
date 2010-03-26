<?php
/**
 * @package modules.forums.lib.services
 */
class forums_ModuleService extends ModuleBaseService
{
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
	 * @return String
	 */
	public function getIp()
	{
		$ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null;
		if (f_util_StringUtils::isEmpty($ip))
		{
			$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
		}
		return $ip;
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
		return $ps->hasPermission($member->getUser(), $permission, $document->getId());
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
		return $ps->hasPermission($member->getUser(), $permission, $documentId);
	}
	
	/**
	 * @return String
	 */
	public function getGlobalAllReadDate()
	{
		return date_Calendar::getInstance()->sub(date_Calendar::MONTH, 1)->toString();
		//return date_Calendar::getInstance()->sub(date_Calendar::HOUR, 12)->toString();
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
	private function findProjectedTotal($website, $websiteField, $service, $fromDate, $toDate, $dateToCompare)
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
}