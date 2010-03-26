<?php
/**
 * forums_PostService
 * @package modules.forums
 */
class forums_PostService extends f_persistentdocument_DocumentService
{
	/**
	 * @var forums_PostService
	 */
	private static $instance;
	
	/**
	 * @return forums_PostService
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
	 * @return forums_persistentdocument_post
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_forums/post');
	}
	
	/**
	 * Create a query based on 'modules_forums/post' model.
	 * Return document that are instance of modules_forums/post,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_forums/post');
	}
	
	/**
	 * Create a query based on 'modules_forums/post' model.
	 * Only documents that are strictly instance of modules_forums/post
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_forums/post', false);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $parent
	 * @return rss_FeedWriter
	 */
	public function getRSSFeedWriterByParent($parent, $recursive = false)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::published());
		if ($parent instanceof forums_persistentdocument_thread)
		{
			$query->add(Restrictions::eq('thread', $parent));
		}
		else if ($parent instanceof forums_persistentdocument_forum)
		{
			$subQuery1 = $query->createCriteria('thread');
			if ($recursive)
			{
				$parent = $parent->getTopic();
				$subQuery2 = $subQuery1->createCriteria('forum');
				$subQuery3 = $subQuery2->createCriteria('topic');
				$subQuery3->add(Restrictions::orExp(Restrictions::eq('id', $parent->getId()), Restrictions::descendentOf($parent->getId())));
			}
			else
			{
				$subQuery1->add(Restrictions::eq('forum', $parent));
			}
		}
		else if ($parent instanceof website_persistentdocument_website || $parent instanceof website_persistentdocument_topic)
		{
			$subQuery1 = $query->createCriteria('thread');
			$subQuery2 = $subQuery1->createCriteria('forum');
			if ($recursive)
			{
				$subQuery3 = $subQuery2->createCriteria('topic');
				$subQuery3->add(Restrictions::descendentOf($parent->getId()));
			}
			else
			{
				$subQuery2->add(Restrictions::eq('topic', $parent));
			}
		}
		else
		{
			throw new BaseException('Invalid parent type: '.$parent->getDocumentModelName());
		}
		
		$limit = forums_ModuleService::getInstance()->getRssMaxItemCount();
		if ($limit > 0)
		{
			$query->setMaxResults($limit);
		}
		$query->addOrder(Order::desc('document_creationdate'));
		$posts = $query->find();
		
		$writer = new rss_FeedWriter();
		foreach ($posts as $post)
		{
			$writer->addItem($post);
		}
		return $writer;
	}
	
	/**
	 * @param forums_persistentdocument_post $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		parent::preInsert($document, $parentNodeId);
		
		$document->setInsertInTree(false);
		
		$document->setIp(forums_ModuleService::getInstance()->getIp());
		if ($document->getPostauthor() === null)
		{
			$document->setPostauthor(forums_MemberService::getInstance()->getCurrentMember());
		}
		
		if ($parentNodeId !== null && $document->getThread() === null)
		{
			$parent = DocumentHelper::getDocumentInstance($parentNodeId);
			if ($parent instanceof forums_persistentdocument_thread)
			{
				$document->setThread($parent);
			}
		}
						
		if ($document->getLabel() === null)
		{
			$replacements = array(
				'author' => ($document->getPostauthor() !== null) ? $document->getPostauthor()->getLabel() : '[...]'
			);
			$document->setLabel(f_Locale::translate('&modules.forums.document.post.Label-patern;', $replacements));
		}
	}
	
	/**
	 * @param forums_persistentdocument_post $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);
		
		if ($document->isPropertyModified('text'))
		{
			$document->setText(website_BBCodeService::getInstance()->fixContent($document->getText()));
		}
	}
	
	/**
	 * @param forums_persistentdocument_post $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId = null)
	{
		parent::postInsert($document, $parentNodeId);
		
		$thread = $document->getThread();
		if ($thread->getFirstPost() === null)
		{
			$thread->setFirstPost($document);
		}
		
		$document->setNumber($thread->getNbpost() + 1);
		$this->pp->updateDocument($document);
		
		$thread->setNbpost($thread->getNbpost() + 1);
		$thread->setModificationdate(date_Calendar::now()->toString());
		if ($thread->getTofollow() === null && $document->getNumber() > 1)
		{
			$thread->setTofollow($document);
		}
		$thread->setLastPostDate($document->getCreationdate());
		$this->pp->updateDocument($thread);
		
		$forum = $thread->getForum();
		$forum->setNbpost($forum->getNbpost() + 1);
		$this->pp->updateDocument($forum);
	}
	
	/**
	 * @param forums_persistentdocument_post $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		$forum = $document->getForum();
		return $forum->getDocumentService()->getWebsiteId($forum);
	}
	
	/**
	 * @param forums_persistentdocument_post $document
	 * @return website_persistentdocument_page or null
	 */
	public function getDisplayPage($document)
	{
		$document = DocumentHelper::getByCorrection($document);
		
		$model = $document->getPersistentModel();
		if ($model->hasURL() && $document->isPublished())
		{
			$topic = $document->getThread()->getForum()->getTopic();
			$page = website_PageService::getInstance()->createQuery()->add(Restrictions::childOf($topic->getId()))->add(Restrictions::published())->add(Restrictions::hasTag('functional_forums_post-detail'))->findUnique();
			return $page;
		}
		return null;
	}
}