<?php
/**
 * @package modules.forums
 * @method forums_ForumgroupService getInstance()
 */
class forums_ForumgroupService extends f_persistentdocument_DocumentService
{
	/**
	 * @return forums_persistentdocument_forumgroup
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_forums/forumgroup');
	}

	/**
	 * Create a query based on 'modules_forums/forumgroup' model.
	 * Return document that are instance of modules_forums/forumgroup,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_forums/forumgroup');
	}
	
	/**
	 * Create a query based on 'modules_forums/forumgroup' model.
	 * Only documents that are strictly instance of modules_forums/forumgroup
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_forums/forumgroup', false);
	}
	
	/**
	 * @param integer $parentId
	 * @return forums_persistentdocument_forumgroup[]
	 */
	public function getByTopicParentId($parentId)
	{
		$query = $this->createQuery()->add(Restrictions::published());
		$query->createCriteria('topic')->add(Restrictions::childOf($parentId));
		return $query->find();
	}

	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		parent::preSave($document, $parentNodeId);
		
		$parent = $this->getMountParent($document, $parentNodeId);
		
		// Generate or move the topic.
		$topic = $document->getTopic();
		if ($topic === null)
		{
			$topic = website_SystemtopicService::getInstance()->getNewDocumentInstance();
			$topic->setReferenceId(abs($document->getId()));
			$topic->setLabel($document->getLabel());
			$topic->setDescription($document->getDescription());
			$topic->setPublicationstatus('DRAFT');
			$topic->save($parent->getId());
			$document->setTopic($topic);
			$document->setWebsite(DocumentHelper::getDocumentInstance($topic->getDocumentService()->getWebsiteId($topic)));
		}
		else if ($parent !== $this->getParentOf($topic))
		{
			if ($document->getWebsite()->getId() != $parent->getDocumentService()->getWebsiteId($parent))
			{
				throw new BaseException('You can only move a forum inside the same website!', 'modules.forums.document.forum.exception.Cant-move-to-another-website');
			}
			$topic->getDocumentService()->moveTo($topic, $parent->getId());
		}
		
		// Recompile locked and excludeFromRss if needed.
		if ($document->isPropertyModified('lockedMode'))
		{
			$this->refreshProperty($document, 'locked', $parentNodeId);
		}
		if ($document->isPropertyModified('excludeFromRssMode'))
		{
			$this->refreshProperty($document, 'excludeFromRss', $parentNodeId);
		}
	}
	
	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @param string $propertyName
	 */
	private function refreshProperty($document, $propertyName, $parentNodeId)
	{
		$setter = 'set'.ucfirst($propertyName);
		switch ($document->{'get'.ucfirst($propertyName).'Mode'}())
		{
			case forums_ModuleService::EXTBOOL_INHERIT:
				$document->{$setter}($this->getForumgroupParent($document, $parentNodeId)->{'get'.ucfirst($propertyName)}());
				break;
				
			case forums_ModuleService::EXTBOOL_TRUE:
				$document->{$setter}(true);
				break;
				
			case forums_ModuleService::EXTBOOL_FALSE:
				$document->{$setter}(false);
				break;
		}
	}
		
	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return forums_persistentdocument_forumgroup
	 */
	protected function getForumgroupParent($document, $parentNodeId)
	{
		$parent = $document->getDocumentService()->getParentOf($document);
		if ($parent === null)
		{
			$parent = DocumentHelper::getDocumentInstance($parentNodeId);
		}
		if ($parent instanceof forums_persistentdocument_forumgroup)
		{
			return $parent;
		}
		throw new Exception('Bad parent type: ' . get_class($parent));
	}

	/**
	 * @param forums_persistentdocument_forum $forum
	 * @return forums_persistentdocument_post
	 */
	public function getLastPostRecursive($forum)
	{
		$query = forums_PostService::getInstance()->createQuery()
			->add(Restrictions::isNull('deleteddate'))
			->addOrder(Order::desc('document_creationdate'))
			->setFirstResult(0)->setMaxResults(1);
		$criteria = $query->createCriteria('thread')->createCriteria('forum');
		$criteria->add(Restrictions::orExp(Restrictions::eq('id', $forum->getId()), Restrictions::descendentOf($forum->getId())));
		return f_util_ArrayUtils::firstElement($query->find());
	}
	
	/**
	 * @param forums_persistentdocument_forum $forum
	 * @return forums_persistentdocument_post
	 */
	public function getInfosRecursive($forum)
	{
		$query = forums_ForumService::getInstance()->createQuery();
		$query->add(Restrictions::orExp(Restrictions::eq('id', $forum->getId()), Restrictions::descendentOf($forum->getId())));
		$query->setProjection(Projections::sum('nbpost', 'nbpostrecursive'), Projections::sum('nbthread', 'nbthreadrecursive'));
		return f_util_ArrayUtils::firstElement($query->find());
	}
	
	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @param integer $parentNodeId
	 */
	protected function postSave($document, $parentNodeId)
	{
		parent::postSave($document, $parentNodeId);
		
		// Recompile locked and excludeFromRss if needed.
		if ($document->isPropertyModified('locked'))
		{
			$this->refreshPropertyOnChildren($document, 'locked');
		}
		if ($document->isPropertyModified('excludeFromRss'))
		{
			$this->refreshPropertyOnChildren($document, 'excludeFromRss');
		}
	}
	
	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId)
	{
		// Update related topics.
		if ($document->isPropertyModified('description') || $document->isPropertyModified('label'))
		{
			$topic = $document->getTopic();
			$topic->setLabel($document->getLabel());
			$topic->setDescription($document->getDescription());
			$topic->save();
		}
	}
	
	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @param string $propertyName
	 */
	private function refreshPropertyOnChildren($document, $propertyName)
	{
		$setter = 'set'.ucfirst($propertyName);
		$getter = 'get'.ucfirst($propertyName);
		$query = $this->createQuery()->add(Restrictions::eq($propertyName.'Mode', forums_ModuleService::EXTBOOL_INHERIT))
				->add(Restrictions::childOf($document->getId()));
		foreach ($query->find() as $forum)
		{
			$forum->{$setter}($document->{$getter}());
			$forum->save();
		}
	}
	
	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return f_persistentdocument_PersistentDocument topic or website
	 */
	protected function getMountParent($document, $parentNodeId)
	{
		// Check parent type.
		$parent = $document->getMountParent();
		if ($parent === null)
		{
			throw new BaseException('Forum groups must have a mount parent!', 'modules.forums.document.forumgroup.exception.Mount-parent-required');
		}
		else if (!($parent instanceof website_persistentdocument_topic) && !($parent instanceof website_persistentdocument_website))
		{
			throw new BaseException('Forum groups mount parent must be a topic or a website!', 'modules.forums.document.forumgroup.exception.Mount-parent-bad-type');
		}
		return $parent;
	}
	
	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @param integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId = null)
	{
		parent::postInsert($document, $parentNodeId);
		
		$topic = $document->getTopic();
		$topic->setReferenceId($document->getId());
		$topic->save();
		$topic->activate();
	}

	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @param string $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		// Status transit from ACTIVE to PUBLISHED.
		if ($document->isPublished())
		{
			$document->getTopic()->activate();
		}
		// Status transit from PUBLISHED to ACTIVE.
		else if ($oldPublicationStatus == 'PUBLISHED')
		{
			$document->getTopic()->deactivate();
		}
	}
	
	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		$topic = $document->getTopic();
		return $topic->getDocumentService()->getWebsiteId($topic);
	}
	
	/**
	 * @param forums_persistentdocument_forumgroup $document
	 * @return website_persistentdocument_page or null
	 */
	public function getDisplayPage($document)
	{
		$document = DocumentHelper::getByCorrection($document);
		
		$model = $document->getPersistentModel();
		if ($model->hasURL() && $document->isPublished())
		{
			$topic = $document->getTopic();
			$page = website_PageService::getInstance()->createQuery()
				->add(Restrictions::childOf($topic->getId()))
				->add(Restrictions::published())
				->add(Restrictions::hasTag('functional_forums_forum-list'))
				->findUnique();
			return $page;
		}
		return null;
	}
	
	/**
	 * @param forums_persistentdocument_websitefolder $document
	 * @param array<string, string> $attributes
	 * @param integer $mode
	 * @param string $moduleName
	 */
	public function completeBOAttributes($document, &$attributes, $mode, $moduleName)
	{
		$topic = $document->getTopic();
		$attributes['topicId'] = $topic->getId();
		if ($mode & DocumentHelper::MODE_CUSTOM)
		{
			$attributes['path'] = $document->getDocumentService()->getPathOf($topic);
		}
	}
	
	/**
	 *
	 * @param forums_persistentdocument_forumgroup $document
	 * @return list_persistentdocument_list | null
	 */
	public function getFlagListRecursively($document)
	{
		$list = $document->getFlagList();
	
		// If list is null try to get it on the parent.
		if ($list == null)
		{
				
			$parent = $document->getDocumentService()->getParentOf($document);
				
			if ($parent != null && $parent instanceof forums_persistentdocument_forumgroup)
			{
				$list = $document->getDocumentService()->getFlagListRecursively($parent);
			}
	
		}
	
		return $list;
	}
}