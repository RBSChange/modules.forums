<?php
/**
 * @package modules.forums
 * @method forums_ModuleService getInstance()
 */
class forums_PostService extends f_persistentdocument_DocumentService
{
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
		return $this->getPersistentProvider()->createQuery('modules_forums/post');
	}
	
	/**
	 * Create a query based on 'modules_forums/post' model.
	 * Only documents that are strictly instance of modules_forums/post
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_forums/post', false);
	}
	
	/**
	 * @param forums_persistentdocument_post $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
	public function getSolrsearchResultItemTemplate($document, $bockName)
	{
		return array('module' => 'forums', 'template' => 'Forums-Inc-PostResultDetail');
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $parent
	 * @return rss_FeedWriter
	 */
	public function getRSSFeedWriterByParent($parent, $recursive = false)
	{
		$query = $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::isNull("deleteddate"));
		$subQuery1 = $query->createCriteria('thread');
		$subQuery2 = $subQuery1->createCriteria('forum');
		$subQuery2->add(Restrictions::eq('excludeFromRss', false));
		if ($parent instanceof forums_persistentdocument_thread)
		{
			$query->add(Restrictions::eq('thread', $parent));
		}
		else if ($parent instanceof forums_persistentdocument_forum)
		{
			if ($recursive)
			{
				$parent = $parent->getTopic();
				$subQuery3 = $subQuery2->createCriteria('topic');
				$subQuery3->add(Restrictions::orExp(Restrictions::eq('id', $parent->getId()), Restrictions::descendentOf($parent->getId())));
			}
			else
			{
				$subQuery1->add(Restrictions::eq('forum', $parent));
			}
		}
		else if ($parent instanceof forums_persistentdocument_forumgroup)
		{
			$parent = $parent->getTopic();
			$subQuery3 = $subQuery2->createCriteria('topic');
			$subQuery3->add(Restrictions::descendentOf($parent->getId()));
		}
		else if ($parent instanceof website_persistentdocument_website || $parent instanceof website_persistentdocument_topic)
		{
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
	 * @param integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		parent::preInsert($document, $parentNodeId);
		
		$document->setInsertInTree(false);
		if ($document->getIp() === null)
		{
			$ip = RequestContext::getInstance()->getClientIp();
			$document->setIp(trim(f_util_ArrayUtils::lastElement(explode(',', $ip))));
			$document->setMeta('author_IP', $ip);
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
			$author = $document->getAuthoridInstance();
			$replacements = array(
				'author' => ($author !== null) ? $author->getLabel() : '[...]'
			);
			$ls = LocaleService::getInstance();
			$rc = RequestContext::getInstance();
			$document->setLabel($ls->formatKey($rc->getLang(), 'm.forums.document.post.label-patern', array('ucf'), $replacements));
		}
	}
	
	/**
	 * @param forums_persistentdocument_post $document
	 * @param integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);
		
		if ($document->isPropertyModified('deleteddate'))
		{
			$document->deleteddateModified = true;
		}
	}
	
	/**
	 * @param forums_persistentdocument_post $document
	 * @param integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId = null)
	{
		parent::postUpdate($document, $parentNodeId);
		
		if (isset($document->deleteddateModified))
		{
			$thread = $document->getThread();
			$lastPost = $thread->getDocumentService()->getLastPost($thread);
			if ($lastPost !== null)
			{
				$thread->setLastPostDate($lastPost->getCreationdate());
			}
			else
			{
				$thread->setLastPostDate(null);
			}
			$this->getPersistentProvider()->updateDocument($thread);
			unset($document->deleteddateModified);
		}
	}
	
	/**
	 * @param forums_persistentdocument_post $document
	 * @param integer $parentNodeId Parent node ID where to save the document.
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
		$this->getPersistentProvider()->updateDocument($document);
		
		$thread->setNbpost($thread->getNbpost() + 1);
		$thread->setModificationdate(date_Calendar::now()->toString());
		if ($thread->getTofollow() === null && $document->getNumber() > 1)
		{
			$thread->setTofollow($document);
		}
		$thread->setLastPostDate($document->getCreationdate());
		$this->getPersistentProvider()->updateDocument($thread);
		
		$forum = $thread->getForum();
		$forum->setNbpost($forum->getNbpost() + 1);
		$this->getPersistentProvider()->updateDocument($forum);
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
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param forums_persistentdocument_post $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
	{
		$parameters['postId'] = $document->getId();
		$link = $urlRewritingService->getDocumentLinkForWebsite($document->getThread(), $website, $lang, $parameters);
		if ($link) {$link->setFragment($document->getAnchor());}
		return $link;
	}
	
	/**
	 * @param forums_persistentdocument_post $document
	 * @param change_Request $request
	 * @return array($module, $action)
	 */
	public function getResolveDetail($document, $request)
	{
		$website = website_WebsiteService::getInstance()->getCurrentWebsite();
		$lang = RequestContext::getInstance()->getLang();
		$link = $document->getDocumentService()->getWebLink(website_UrlRewritingService::getInstance(), $document, $website, $lang, array());
		$request->setParameter('location', $link->getUrl());
		return array('website', 'Redirect');
	}
		
	/**
	 * @param indexer_IndexedDocument $indexedDocument
	 * @param forums_persistentdocument_post $document
	 * @param indexer_IndexService $indexService
	 */
	protected function updateIndexDocument($indexedDocument, $document, $indexService)
	{
		if ($document->getDeleteddate() !== null)
		{
			$indexedDocument->foIndexable(false);
		}
		
		if ($document->isFirstPostInThread())
		{
			$indexedDocument->setLabel($document->getThreadLabel());	
		}
	}
	
	/**
	 * @param integer $userId
	 * @return integer
	 */
	public function getCountByAuthorid($userId)
	{
		$row = $this->createQuery()->add(Restrictions::eq('authorid', $userId))->setProjection(Projections::rowCount('nb'))->findUnique();
		return $row['nb'];
	}
	
	/**
	 * @param forums_persistentdocument_post $post
	 * @param users_persistentdocument_user $user
	 */
	public function setAsReadForUser($post, $user)
	{
		if (!$user) { return; }		
		try
		{
			$this->getTransactionManager()->beginTransaction();
			
			$profile = forums_ForumsprofileService::getInstance()->getByAccessorId($user->getId(), true);
			$allReadDate = $profile->getDocumentService()->getAllReadDate($profile);
			
			// By thread...
			$track = $profile->getDecodedTrackingByThread();
			if (!is_array($track)) {$track = array();}
			$thread = $post->getThread();
			$threadId = $thread->getId();
			$postDate = $post->getCreationdate();
			if (!isset($track[$threadId]))
			{
				$profile->setTempLastReadDateByThreadId($threadId, $allReadDate);
			}
			else if ($track[$threadId] < $postDate)
			{
				$profile->setTempLastReadDateByThreadId($threadId, $track[$threadId]);
			}
			if ($postDate > $allReadDate)
			{
				if (!isset($track[$threadId]) || $track[$threadId] < $postDate)
				{
					$track[$threadId] = $postDate;
				}
			}
			else if (isset($track[$threadId]) && $track[$threadId] <= $allReadDate)
			{
				unset($track[$threadId]);
			}
			$profile->setTrackingByThread($track);
			
			// By forum...
			$track = $profile->getDecodedTrackingByForum();
			if (!is_array($track)) {$track = array();}
			$forumId = $thread->getForum()->getId();
			if ($postDate > $allReadDate)
			{
				if (!isset($track[$forumId]) || $track[$forumId] < $postDate)
				{
					$track[$forumId] = $postDate;
				}
			}
			else if (isset($track[$forumId]) && $track[$forumId] <= $allReadDate)
			{
				unset($track[$forumId]);
			}
			$profile->setTrackingByForum($track);
			
			$this->getPersistentProvider()->updateDocument($profile);
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
		}
	}
}