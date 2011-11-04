<?php
/**
 * forums_ForumsprofileService
 * @package modules.forums
 */
class forums_ForumsprofileService extends users_ProfileService
{
	/**
	 * @var forums_ForumsprofileService
	 */
	private static $instance;

	/**
	 * @return forums_ForumsprofileService
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
	 * @return forums_persistentdocument_forumsprofile
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_forums/forumsprofile');
	}

	/**
	 * Create a query based on 'modules_forums/forumsprofile' model.
	 * Return document that are instance of forums_persistentdocument_forumsprofile,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_forums/forumsprofile');
	}
	
	/**
	 * Create a query based on 'modules_forums/forumsprofile' model.
	 * Only documents that are strictly instance of forums_persistentdocument_forumsprofile
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_forums/forumsprofile', false);
	}

	/**
	 * @param integer $accessorId
	 * @param boolean $required
	 * @return forums_persistentdocument_forumsprofile || null
	 */
	public function getByAccessorId($accessorId, $required = false)
	{
		return parent::getByAccessorId($accessorId, $required);
	}
	
	/**
	 * @return forums_persistentdocument_forumsprofile
	 */
	public function getCurrent()
	{
		return parent::getCurrent();
	}
	
	/**
	 * @param forums_persistentdocument_forumsprofile $document
	 * @param string[] $propertiesName
	 * @param array $datas
	 * @param integer $accessorId
	 */
	public function addFormProperties($document, $propertiesName, &$datas, $accessorId = null)
	{
		if ($document->isNew()) {$datas['id'] = 0;}
		if ($document->getForumMemberTitleCount() > 0) {$datas['forumMemberTitle'] = implode(',', $document->getForumMemberTitleIds());}
		if ($document->getSignature()) {$datas['signature'] = $document->getSignatureAsBBCode();}
	}
	
	/**
	 * @param forums_persistentdocument_forumsprofile $profile
	 * @return string
	 */
	public function getAllReadDate($profile = null)
	{
		if ($profile !== null && $profile->getLastAllRead() !== null)
		{
			return max($profile->getLastAllRead(), forums_ModuleService::getInstance()->getGlobalAllReadDate());
		}
		return forums_ModuleService::getInstance()->getGlobalAllReadDate();
	}
	
	/**
	 * @param forums_persistentdocument_forumsprofile $profile
	 */
	public function cleanTracking($profile)
	{
		try
		{
			$this->tm->beginTransaction();
			
			$date = $this->getAllReadDate($profile);
		
			// By thread...
			$track = $profile->getDecodedTrackingByThread();
			foreach ($track as $key => $value)
			{
				if ($date >= $value)
				{
					unset($track[$key]);
				}
			}
			$profile->setTrackingByThread($track);
			
			// By forum...
			$track = $profile->getDecodedTrackingByForum();
			foreach ($track as $key => $value)
			{
				if ($date >= $value)
				{
					unset($track[$key]);
				}
			}
			$profile->setTrackingByForum($track);
			
			$this->pp->updateDocument($profile);
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}
}