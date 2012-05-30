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
			$query->setFirstResult($start);
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
		$query = forums_PostService::getInstance()->createQuery()->add(Restrictions::eq('thread', $thread))->add(Restrictions::isNull('deleteddate'))->addOrder(Order::desc('document_creationdate'))->setFirstResult(0)->setMaxResults(1);
		return f_util_ArrayUtils::firstElement($query->find());
	}
	
	/**
	 * @param forums_persistentdocument_thread $thread
	 * @param String $date
	 * @return forums_persistentdocument_post
	 */
	public function getFirstUnreadPost($thread, $date)
	{
		$query = forums_PostService::getInstance()->createQuery()->add(Restrictions::eq('thread', $thread))->add(Restrictions::isNull('deleteddate'))->add(Restrictions::gt('creationdate', $date))->addOrder(Order::asc('document_creationdate'))->setFirstResult(0)->setMaxResults(1);
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
		return LinkHelper::getDocumentUrl($thread);
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $plannedTask
	 */
	public function sendToFollowers($plannedTask = null)
	{
		$errors = array();
		$batchPath = 'modules/forums/lib/bin/SendNotificationsToFollowersBatch.php';
		$threads = $this->createQuery()->add(Restrictions::isNotNull('tofollow'))->find();
		foreach ($threads as $thread)
		{
			if ($plannedTask instanceof task_persistentdocument_plannedtask)
			{
				$plannedTask->ping();
			}
			$result = f_util_System::execScript($batchPath, array($thread->getId()));
			// Log fatal errors...
			if ($result != 'OK')
			{
				if ($plannedTask instanceof task_persistentdocument_plannedtask)
				{
					$errors[] = $result;
				}
				else
				{
					Framework::error(__METHOD__ . ' ' . $batchPath . ' an error occured: "' . $result . '"');
				}
			}
		}
		
		if (count($errors))
		{
			throw new Exception(implode("\n", $errors));
		}
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return forums_persistentdocument_thread[]
	 */
	public function getGlobalAnnoucements($website = null)
	{
		if ($website === null)
		{
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		}
		$query = $this->createQuery()->add(Restrictions::eq('level', self::LEVEL_GLOBAL));
		$query->createCriteria('forum')->add(Restrictions::eq('website', $website));
		$query->add(Restrictions::published())->addOrder(Order::desc('lastpostdate'));
		return $query->find();
	}
	
	/**
	 * Count the number of Thread in a forum
	 * @param unknown_type $forum
	 */
	public function countByForum($forum)
	{
		$row = forums_ThreadService::getInstance()->createQuery()->add(Restrictions::published())->add(Restrictions::eq('forum', $forum->getId()))->add(Restrictions::ne('level', self::LEVEL_GLOBAL))->setProjection(Projections::rowCount('count'))->findUnique();
		return $row['count'];
	}
	
	/**
	 * @param forums_persistentdocument_forum $forum
	 * @return forums_persistentdocument_threads[]
	 */
	public function getByForum($forum, $offset = null, $limit = null)
	{
		$query = forums_ThreadService::getInstance()->createQuery()->add(Restrictions::published())->add(Restrictions::eq('forum', $forum->getId()))->add(Restrictions::ne('level', self::LEVEL_GLOBAL))->addOrder(Order::desc('level'))->addOrder(Order::desc('lastpostdate'));
		if ($offset !== null && $limit !== null)
		{
			$query->setFirstResult($offset);
			$query->setMaxResults($limit);
		}
		return $query->find();
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $parent
	 * @return rss_FeedWriter
	 */
	public function getRSSFeedWriterByParent($parent, $recursive = false)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::published());
		$subQuery1 = $query->createCriteria('forum');
		$subQuery1->add(Restrictions::eq('excludeFromRss', false));
		if ($parent instanceof forums_persistentdocument_forum)
		{
			if ($recursive)
			{
				$parent = $parent->getTopic();
				$subQuery2 = $subQuery1->createCriteria('topic');
				$subQuery2->add(Restrictions::orExp(Restrictions::eq('id', $parent->getId()), Restrictions::descendentOf($parent->getId())));
			}
			else
			{
				$query->add(Restrictions::eq('forum', $parent));
			}
		}
		else if ($parent instanceof forums_persistentdocument_forumgroup)
		{
			$parent = $parent->getTopic();
			$subQuery2 = $subQuery1->createCriteria('topic');
			$subQuery2->add(Restrictions::descendentOf($parent->getId()));
		}
		else if ($parent instanceof website_persistentdocument_website || $parent instanceof website_persistentdocument_topic)
		{
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
			throw new BaseException('Invalid parent type: ' . $parent->getDocumentModelName());
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
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	public function postUpdate($document, $parentNodeId = null)
	{
		if ($document->isPropertyModified('forum'))
		{
			// Refresh old forum...
			$oldId = $document->getForumOldValueId();
			if ($oldId)
			{
				try
				{
					$forum = DocumentHelper::getDocumentInstance($oldId);
					$this->refreshCounts($forum);
				}
				catch (Exception $e)
				{
					// The forum doesn't exist any more.
				}
			}
			
			// Refresh new forum...
			$forum = $document->getForum();
			if ($forum !== null)
			{
				$this->refreshCounts($forum);
			}
		}
	}
	
	/**
	 * @param forums_persistentdocument_forum $forum
	 */
	public function refreshCounts($forum)
	{
		try
		{
			$this->tm->beginTransaction();
			
			// Thread count...
			$query = forums_ThreadService::getInstance()->createQuery()->add(Restrictions::eq('forum', $forum));
			$query->setProjection(Projections::rowCount('count'));
			$count = f_util_ArrayUtils::firstElement($query->findColumn('count'));
			$forum->setNbthread($count);
			
			// Post count...
			$query = forums_PostService::getInstance()->createQuery();
			$query->createCriteria('thread')->add(Restrictions::eq('forum', $forum));
			$query->setProjection(Projections::rowCount('count'));
			$count = f_util_ArrayUtils::firstElement($query->findColumn('count'));
			$forum->setNbpost($count);
			
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
		$data['properties']['path'] = $this->getPathOf($document);
		
		return $data;
	}
	
	/**
	 * @param array $params
	 * @return array
	 */
	public function getNotificationParameters($params)
	{
		$parameters = array();
		
		$thread = $params['thread'];
		$parameters['TOPIC'] = $thread->getLabelAsHtml();
		$parameters['LINK'] = '<a class="link" href="' . $thread->getTofollow()->getPostUrlInThread() . '">' . LocaleService::getInstance()->transFO('m.forums.frontoffice.thislink') . '</a>';
		
		if (isset($params['member']) && $params['member'] instanceof forums_persistentdocument_member)
		{
			$member = $params['member'];
			$parameters['PSEUDO'] = $member->getLabelAsHtml();
		}
		
		if (isset($params['specificParams']) && is_array($params['specificParams']))
		{
			$parameters = array_merge($parameters, $params['specificParams']);
		}
		return $parameters;
	}
	
	/**
	 * @param forums_persistentdocument_member $member
	 * @param integer $max the maximum number of threads that can treat
	 * @return integer the number of treated threads
	 */
	public function treatThreadsForMemberDeletion($member, $max)
	{
		$count = 0;
		foreach (array('threadauthor', 'privatenoteby', 'followers') as $fieldName)
		{
			$query = $this->createQuery();
			$query->add(Restrictions::eq($fieldName, $member));
			$query->setFirstResult(0)->setMaxResults($max - $count);
			$threads = $query->find();
			foreach ($threads as $thread)
			{
				/* @var $thread forums_persistentdocument_thread */
				$thread->getDocumentService()->treatThreadForMemberDeletion($thread, $member);
			}
			$count += count($threads);
		}
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' ' . $count . ' threads treated');
		}
		return $count;
	}
	
	/**
	 * @param forums_persistentdocument_thread $thread
	 * @param forums_persistentdocument_member $member
	 */
	protected function treatThreadForMemberDeletion($thread, $member)
	{
		if (DocumentHelper::equals($thread->getThreadauthor(), $member))
		{
			$thread->setThreadauthor(null);
			$thread->setMeta('threadAuthorDeletedMember', $member->getLabel() . ' (' . $member->getId() . ')');
		}
		
		if (DocumentHelper::equals($thread->getPrivatenoteby(), $member))
		{
			$thread->setPrivatenoteby(null);
		}
		
		$thread->removeFollowers($member);
		$thread->save();
	}
	
	/**
	 * Get the label of flag on the thread
	 * @param forums_persistentdocument_thread $thread
	 * @return string
	 */
	public function getFlagLabel($thread)
	{
		$flagLabel = '';
		$flag = $thread->getFlag();
		if ($flag != null)
		{
			// Get forum
			$forum = $thread->getForum();
			
			// Get flag list of Forum
			$list = $forum->getDocumentService()->getFlagListRecursively($forum);
			
			// Search item in list
			if ($list != null)
			{
				$item = $list->getItemByValue($flag);
				if ($item != null)
				{
					$flagLabel = $item->getLabel();
				}
			}
			
			if ($flagLabel == '')
			{
				$flagLabel = $flag;
			}
		}
		return $flagLabel;
	}
}