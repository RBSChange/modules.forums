<?php
/**
 * forums_ThreadService
 * @package modules.forums
 */
class forums_ThreadService extends f_persistentdocument_DocumentService
{
	const LEVEL_NORMAL = 10;
	const LEVEL_STICKY = 20;
	const LEVEL_ANNOUNCEMENT = 30;
	const LEVEL_GLOBAL = 40;
	
	/**
	 * @var forums_ThreadService
	 */
	private static $instance;
	
	/**
	 * @return forums_ThreadService
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
	 * @return forums_persistentdocument_thread
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_forums/thread');
	}
	
	/**
	 * Create a query based on 'modules_forums/thread' model.
	 * Return document that are instance of modules_forums/thread,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_forums/thread');
	}
	
	/**
	 * Create a query based on 'modules_forums/thread' model.
	 * Only documents that are strictly instance of modules_forums/thread
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_forums/thread', false);
	}
	
	/**
	 * @param forums_persistentdocument_thread $thread
	 * @param String $text
	 */
	public function addPost($thread, $text)
	{
		$post = forums_PostService::getInstance()->getNewDocumentInstance();
		$post->setText($text);
		$post->setThread($thread);
		$post->save();
		$post->getDocumentService()->activate($post->getId());
	}
	
	/**
	 * @param forums_persistentdocument_thread $thread
	 * @param Integer $start
	 * @param Integer $limit
	 * @return forums_persistentdocument_post[]
	 */
	public function getPosts($thread, $start = null, $limit = 20, $order = 'asc')
	{
		$query = forums_PostService::getInstance()->createQuery()->add(Restrictions::published());
		$query->add(Restrictions::eq('thread', $thread->getId()));
		if ($start !== null)
		{
			$query->add(Restrictions::ge('number', $start));
		}
		$query->setMaxResults($limit);
		if ($order == 'desc')
		{
			$query->addOrder(Order::desc('number'));
		}
		else
		{
			$query->addOrder(Order::asc('number'));
		}
		return $query->find();
	}
	
	/**
	 * @param forums_persistentdocument_thread $thread
	 * @return forums_persistentdocument_post
	 */
	public function getLastPost($thread)
	{
		$query = forums_PostService::getInstance()->createQuery()->add(Restrictions::eq('thread', $thread))
			->add(Restrictions::isNull('deleteddate'))
			->addOrder(Order::desc('document_creationdate'))
			->setFirstResult(0)->setMaxResults(1);
		return f_util_ArrayUtils::firstElement($query->find());
	}
	
	/**
	 * @param forums_persistentdocument_thread $thread
	 * @param String $date
	 * @return forums_persistentdocument_post
	 */
	public function getFirstUnreadPost($thread, $date)
	{
		$query = forums_PostService::getInstance()->createQuery()->add(Restrictions::eq('thread', $thread))
			->add(Restrictions::isNull('deleteddate'))
			->add(Restrictions::gt('creationdate', $date))
			->addOrder(Order::asc('document_creationdate'))
			->setFirstResult(0)->setMaxResults(1);
		return f_util_ArrayUtils::firstElement($query->find());
	}
	
	/**
	 * @param forums_persistentdocument_thread $thread
	 * @return String
	 */
	public function getUserUrl($thread)
	{
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if ($member !== null)
		{
			$date = $member->getLastReadDateByThreadId($thread->getId());
			if ($date !== null)
			{
				$post = $this->getFirstUnreadPost($thread, $date);
				if ($post !== null)
				{
					return $post->getPostUrlInThread();
				}
			}
		}
		return LinkHelper::getUrl($thread);
	}

	/**
	 * @param forums_persistentdocument_thread $thread
	 * @param forums_persistentdocument_post $post
	 */
	public function sendToFollowers()
	{
		$ns = notification_NotificationService::getInstance();
		$notif = $ns->getNotificationByCodeName('modules_forums/follower');
		
		$threads = $this->createQuery()->add(Restrictions::isNotNull('tofollow'))->find();
		$sentMails = 0;
		foreach ($threads as $thread)
		{
			$num = 1 + ($thread->getNbpost() - $thread->getTofollow()->getNumber());
			foreach ($thread->getFollowersArray() as $member)
			{
				if ($member->getUser()->isPublished())
				{
					$recipients = new mail_MessageRecipients();
					$recipients->setTo($member->getEmail());
					$replace = array('PSEUDO' => $member->getLabel(), 'TOPIC' => $thread->getLabel(), 'NUM' => $num, 'LINK' => '<a class="link" href="' . $thread->getTofollow()->getPostUrlInThread() . '">' . f_Locale::translate('&modules.forums.frontoffice.thislink;') . '</a>');
					$ns->send($notif, $recipients, $replace, null);
					$sentMails++;
				}
				else
				{
					$thread->removeFollowers($member);
				}
			}
			$thread->setTofollow(null);
			$thread->save();
		}
	}
	
	/**
	 * @return forums_persistentdocument_thread[]
	 */
	public function getGlobalAnnoucements()
	{
		return $this->createQuery()->add(Restrictions::eq('level', self::LEVEL_GLOBAL))->add(Restrictions::published())->addOrder(Order::desc('lastpostdate'))->find();
	}
	
	/**
	 * @param forums_persistentdocument_forum $forum
	 * @return forums_persistentdocument_threads[]
	 */
	public function getByForum($forum)
	{
		return forums_ThreadService::getInstance()->createQuery()->add(Restrictions::published())
			->add(Restrictions::eq('forum', $forum->getId()))
			->add(Restrictions::ne('level', self::LEVEL_GLOBAL))
			->addOrder(Order::desc('level'))
			->addOrder(Order::desc('lastpostdate'))->find();
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $parent
	 * @return rss_FeedWriter
	 */
	public function getRSSFeedWriterByParent($parent, $recursive = false)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::published());
		if ($parent instanceof forums_persistentdocument_forum)
		{
			if ($recursive)
			{
				$parent = $parent->getTopic();
				$subQuery1 = $query->createCriteria('forum');
				$subQuery2 = $subQuery1->createCriteria('topic');
				$subQuery2->add(Restrictions::orExp(Restrictions::eq('id', $parent->getId()), Restrictions::descendentOf($parent->getId())));
			}
			else
			{
				$query->add(Restrictions::eq('forum', $parent));
			}
		}
		else if ($parent instanceof website_persistentdocument_website || $parent instanceof website_persistentdocument_topic)
		{
			$subQuery1 = $query->createCriteria('forum');
			if ($recursive)
			{
				$subQuery2 = $subQuery1->createCriteria('topic');
				$subQuery2->add(Restrictions::descendentOf($parent->getId()));
			}
			else
			{
				$subQuery1->add(Restrictions::eq('topic', $parent));
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
	 * @param forums_persistentdocument_thread $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		parent::preInsert($document, $parentNodeId);
				
		$document->setInsertInTree(false);
		
		if ($document->getThreadauthor() === null)
		{
			$document->setThreadauthor(forums_MemberService::getInstance()->getCurrentMember());
		}
		
		if ($parentNodeId !== null && $document->getForum() === null)
		{
			$parent = DocumentHelper::getDocumentInstance($parentNodeId);
			if ($parent instanceof forums_persistentdocument_forum)
			{
				$document->setForum($parent);
			}
		}
	}
	
	/**
	 * @param forums_persistentdocument_thread $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId = null)
	{
		parent::postInsert($document, $parentNodeId);
		try
		{
			$this->tm->beginTransaction();
			$forum = $document->getForum();
			$forum->setNbthread($forum->getNbthread() + 1);
			$this->pp->updateDocument($forum);
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}
	
	/**
	 * @param forums_persistentdocument_thread $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		$forum = $document->getForum();
		return $forum->getDocumentService()->getWebsiteId($forum);
	}
	
	/**
	 * @param forums_persistentdocument_thread $document
	 * @return website_persistentdocument_page or null
	 */
	public function getDisplayPage($document)
	{
		$document = DocumentHelper::getByCorrection($document);
		
		$model = $document->getPersistentModel();
		if ($model->hasURL() && $document->isPublished())
		{
			$topic = $document->getForum()->getTopic();
			$page = website_PageService::getInstance()->createQuery()->add(Restrictions::childOf($topic->getId()))->add(Restrictions::published())->add(Restrictions::hasTag('functional_forums_thread-detail'))->findUnique();
			return $page;
		}
		return null;
	}
	
	/**
	 * @param forums_persistentdocument_thread $document
	 * @param string $forModuleName
	 * @return array
	 */
	public function getResume($document, $forModuleName)
	{
		$data = parent::getResume($document, $forModuleName);
		
		$data['properties']['nbpost'] = strval($document->getNbpost());

		return $data;
	}
}