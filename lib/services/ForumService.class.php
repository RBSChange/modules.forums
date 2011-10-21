<?php
/**
 * forums_ForumService
 * @package forums
 */
class forums_ForumService extends forums_ForumgroupService
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
			self::$instance = new self();
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
		$page = website_PageService::getInstance()->getCurrentPage();
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
	 * @param forums_persistentdocument_forum $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return f_persistentdocument_PersistentDocument topic or website
	 */
	protected function getMountParent($document, $parentNodeId)
	{
		return $this->getForumgroupParent($document, $parentNodeId)->getTopic();
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
	 * @param forums_persistentdocument_forum $document
	 * @param Integer $destId
	 */
	protected function onDocumentMoved($document, $destId)
	{
		$destination = DocumentHelper::getDocumentInstance($destId);
		if ($destination instanceof forums_persistentdocument_forumgroup)
		{
			$topic = $document->getTopic();
			$destinationTopic = $destination->getTopic();
			if ($topic->getDocumentService()->getParentOf($topic) !== $destinationTopic)
			{
				$this->moveTo($topic, $destinationTopic->getId());
			}
		}
		else
		{
			throw new Exception('A forum must be a chid of a forumgroup!');
		}
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
			$page = website_PageService::getInstance()->createQuery()
				->add(Restrictions::childOf($topic->getId()))
				->add(Restrictions::published())
				->add(Restrictions::hasTag('functional_forums_forum-detail'))
				->findUnique();
			return $page;
		}
		return null;
	}
	
	
	/**
	 * @param forums_persistentdocument_forum $forum
	 */
	public function deleteDelayed($forum)
	{
		$treenode = TreeService::getInstance()->getInstanceByDocument($forum);
		$tm = $this->getTransactionManager();
		try 
		{
			$tm->beginTransaction();
			TreeService::getInstance()->deleteNode($treenode);
			$this->putInTrash($forum->getId());
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
	}
	
	/**
	 * @return Array
	 */
	public function getIdsToDelete()
	{
		$idsToDelete = array();
		$forums = $this->createQuery()->add(Restrictions::eq('publicationstatus', 'TRASH'))->find();
		foreach ($forums as $forum)
		{
			$threadIds = forums_ThreadService::getInstance()->createQuery()
							->add(Restrictions::eq('forum', $forum))->addOrder(Order::desc('id'))->setProjection(Projections::property('id', 'id'))->findColumn('id');
			$idsToDelete = array_merge($idsToDelete, forums_PostService::getInstance()->createQuery()
							->add(Restrictions::in('thread', $threadIds))->addOrder(Order::desc('id'))->setProjection(Projections::property('id', 'id'))->findColumn('id'));
			$idsToDelete = array_merge($idsToDelete, $threadIds);
			$idsToDelete[] = $forum->getId();
		}
		return $idsToDelete;
	}
}