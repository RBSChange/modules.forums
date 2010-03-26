<?php
/**
 * forums_ForumService
 * @package forums
 */
class forums_ForumService extends f_persistentdocument_DocumentService
{
	/**
	 * @var forums_ForumService
	 */
	private static $instance;
	
	/**
	 * @return forums_ForumService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @return forums_persistentdocument_forum
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_forums/forum');
	}
	
	/**
	 * Create a query based on 'modules_forums/forum' model.
	 * Return document that are instance of modules_forums/forum,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_forums/forum');
	}
	
	/**
	 * Create a query based on 'modules_forums/forum' model.
	 * Only documents that are strictly instance of modules_forums/forum
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_forums/forum', false);
	}
	
	/**
	 * @return forums_persistentdocument_forum
	 */
	public function getCurrentForum()
	{
		$page = website_WebsiteModuleService::getInstance()->getCurrentPage();
		if ($page === null)
		{
			return null;
		}
		
		$systemTopics = website_SystemtopicService::getInstance()->createQuery()->add(Restrictions::ancestorOf($page->getId()))->setMaxResults(1)->find();
		if ($systemTopics !== null && $systemTopics->getTarget() instanceof forums_persistentdocument_forum)
		{
			return $systemTopics->getTarget();
		}
		return null;
	}
	
	/**
	 * @param forums_persistentdocument_forum $forum
	 * @return forums_persistentdocument_post
	 */
	public function getLastPost($forum)
	{
		$query = forums_PostService::getInstance()->createQuery()
			->add(Restrictions::isNull('deleteddate'))
			->addOrder(Order::desc('document_creationdate'))
			->setFirstResult(0)->setMaxResults(1);
		$query->createCriteria('thread')->add(Restrictions::eq('forum', $forum));
		return f_util_ArrayUtils::firstElement($query->find());
	}
	
	/**
	 * @param Integer $parentId
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function getByTopicParentId($parentId)
	{
		$query = $this->createQuery()->add(Restrictions::published());
		$query->createCriteria('topic')->add(Restrictions::childOf($parentId));
		return $query->find();
	}
	
	/**
	 * @param catalog_persistentdocument_shop $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);
		
		// Check parent type.
		$parent = $document->getMountParent();
		if ($parent === null)
		{
			throw new BaseException('Forums must have a mount parent!', 'modules.forums.document.forum.exception.Mount-parent-required');
		}
		else if (!($parent instanceof website_persistentdocument_topic) && !($parent instanceof website_persistentdocument_website))
		{
			throw new BaseException('Forums parent must be a topic or a website!', 'modules.forums.document.forum.exception.Mount-parent-bad-type');
		}
		
		// Generate or move the topic.
		$topic = $document->getTopic();
		if ($topic === null)
		{
			$topic = website_SystemtopicService::getInstance()->getNewDocumentInstance();
			$topic->setReferenceId($document->getId());
			$topic->setLabel($document->getLabel());
			$topic->setDescription($document->getDescription());
			$topic->setPublicationstatus('DRAFT');
			$topic->save($parent->getId());
			$document->setTopic($topic);
			$document->setWebsite(DocumentHelper::getDocumentInstance($topic->getDocumentService()->getWebsiteId($topic)));
		}
		else if ($parent !== $this->getParentOf($topic))
		{
			if ($document->setWebsite()->getId() !== $topic->getDocumentService()->getWebsiteId($topic))
			{
				throw new BaseException('You can only move a forum inside the same website!', 'modules.forums.document.forum.exception.Cant-move-to-another-website');
			}
			$topic->getDocumentService()->moveTo($topic, $parent->getId());
		}
	}
	/**
	 * @param forums_persistentdocument_forum $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		parent::preInsert($document, $parentNodeId);
		
		$document->setNbthread(0);
		$document->setNbpost(0);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
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
	 * @param catalog_persistentdocument_shop $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		// Status transit from ACTIVE to PUBLICATED.
		if ($document->isPublished())
		{
			$document->getTopic()->activate();
		}
		// Status transit from PUBLICATED to ACTIVE.
		else if ($oldPublicationStatus == 'PUBLICATED')
		{
			$document->getTopic()->deactivate();
		}
	}
	
	/**
	 * @param forums_persistentdocument_forum $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		$topic = $document->getTopic();
		return $topic->getDocumentService()->getWebsiteId($topic);
	}
	
	/**
	 * @param forums_persistentdocument_forum $document
	 * @return website_persistentdocument_page or null
	 */
	public function getDisplayPage($document)
	{
		$document = DocumentHelper::getByCorrection($document);
		
		$model = $document->getPersistentModel();
		if ($model->hasURL() && $document->isPublished())
		{
			$topic = $document->getTopic();
			$page = website_PageService::getInstance()->createQuery()->add(Restrictions::childOf($topic->getId()))->add(Restrictions::published())->add(Restrictions::hasTag('functional_forums_forum-detail'))->findUnique();
			return $page;
		}
		return null;
	}
	
	/**
	 * @param forums_persistentdocument_forum $document
	 * @param string $forModuleName
	 * @return array
	 */
	public function getResume($document, $forModuleName)
	{
		$data = parent::getResume($document, $forModuleName);
		
		$data['properties']['path'] = $document->getDocumentService()->getPathOf($document->getTopic());
		$data['properties']['nbthread'] = strval($document->getNbthread());
		$data['properties']['nbpost'] = strval($document->getNbpost());

		return $data;
	}
}