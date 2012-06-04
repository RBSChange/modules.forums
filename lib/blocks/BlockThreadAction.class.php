<?php
/**
 * forums_BlockThreadAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockThreadAction extends forums_BlockPostListBaseAction
{
	/**
	 * @return array<String, String>
	 */
	public function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_thread)
		{
			$post = $doc->getFirstPost();
			$description = f_util_StringUtils::shortenString(f_util_HtmlUtils::htmlToText($post->getTextAsHtml()), 100);
			return array('threadname' => $doc->getLabel(), 'forumname' => $doc->getForum()->getLabel(), 'threadshortdesc' => $description, 'threadkeywords' => $doc->getKeywords());
		}
		return array();
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackofficeEdition())
		{
			return website_BlockView::NONE;
		}
		$thread = $this->getDocumentParameter();
		if (!($thread instanceof forums_persistentdocument_thread) || !$thread->isPublished())
		{
			return website_BlockView::NONE;
		}
		$threadService = $thread->getDocumentService();

		if ($request->hasParameter('privatenote') || $request->hasNonEmptyParameter('follow') || $request->hasNonEmptyParameter('unfollow'))
		{	
			$tm = f_persistentdocument_TransactionManager::getInstance();
			try
			{
				$tm->beginTransaction();
				$user = users_UserService::getInstance()->getCurrentUser();
				if ($request->hasParameter('privatenote') && $thread->isEditable())
				{
					$thread->setPrivatenoteAsBBCode($request->getParameter('privatenote'));
					$thread->setPrivatenoteby($user);
					$thread->save();
				}
				
				if ($request->hasNonEmptyParameter('follow'))
				{
					$thread->addFollowers($user);
					$thread->save();
				}
				if ($request->hasNonEmptyParameter('unfollow'))
				{
					$thread->removeFollowers($user);
					$thread->save();
				}
				$tm->commit();
			}
			catch (Exception $e)
			{
				$tm->rollBack($e);
				throw $e;
			}
		}
		$request->setAttribute('thread', $thread);
		
		if ($thread->isEditable())
		{
			$this->getContext()->addScript('modules.forums.lib.js.privatenote');
		}
		
		$itemPerPage = $thread->getForum()->getNbPostPerPage();
		$postIds = $threadService->getPostIds($thread);
		$page = $this->getPageNumber($request, $itemPerPage, $postIds);
		
		$posts = $threadService->getPosts($thread, ($itemPerPage * ($page - 1)) + 1, $itemPerPage);
		$paginator = new paginator_Paginator('forums', $page, $posts, $itemPerPage);
		$paginator->setItemCount($thread->getNbpost());
		$paginator->setExcludeParameters(array('postId'));

		$user = users_UserService::getInstance()->getCurrentUser();
		if ($user !== null && count($posts) > 0)
		{
			$post = f_util_ArrayUtils::lastElement($posts);
			$post->getDocumentService()->setAsReadForUser($post, $user);
		}
		
		if ($thread->canFollow())
		{
			$request->setAttribute('followUrl', LinkHelper::getDocumentUrl($thread, null, array('forumsParam[page]' => $page, 'forumsParam[follow]' => 1)));
		}
		else if ($thread->canUnfollow())
		{
			$request->setAttribute('unfollowUrl', LinkHelper::getDocumentUrl($thread, null, array('forumsParam[page]' => $page, 'forumsParam[unfollow]' => 1)));
		}

		// Post list info.
		$postListInfo = array();
		$postListInfo['displayConfig'] = $this->getDisplayConfig();
		$postListInfo['paginator'] = $paginator;
		$request->setAttribute('postListInfo', $postListInfo);
		
		$this->getContext()->addScript('modules.forums.lib.js.forums');
			
		// Add link rel canonical.
		$this->addCanonical($thread, $page, $request);
		
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param forums_persistentodcument_thread $thread
	 * @param integer $pageNumber
	 * @param f_mvc_Request $request
	 */
	protected function addCanonical($thread, $pageNumber, $request)
	{
		$this->getContext()->addCanonicalParam('page', $pageNumber > 1 ? $pageNumber : null, 'forums');
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param integer $itemPerPage
	 * @param integer[] $postIds
	 */
	protected function getPageNumber($request, $itemPerPage, $postIds)
	{
		// If there is a page set, return it.
		$pageNumber = $request->getParameter('page');
		if ($pageNumber)
		{
			if (floor(count($postIds) / $itemPerPage) + 1 >= $pageNumber)
			{
				return $pageNumber;
			}
		}

		// Else look for a comment id.
		$globalRequest = change_Controller::getInstance()->getRequest();
		$postId = intval($globalRequest->getParameter('postId'));
		if ($postId)
		{
			foreach ($postIds as $index => $id)
			{
				if ($postId == $id)
				{
					return 1 + floor($index / $itemPerPage);
				}
			}
		}

		// Else return the first page.
		return 1;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeChooseforum($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$thread = $this->getDocumentParameter();
		if ($thread === null || !($thread instanceof forums_persistentdocument_thread) || !$thread->isPublished())
		{
			return website_BlockView::NONE;
		}
		$request->setAttribute('thread', $thread);
		
		if (!$this->canModerate($thread))
		{
			$this->addError(LocaleService::getInstance()->trans('m.forums.frontoffice.you-dont-have-permission', array('ucf')));
			return $this->getForbiddenView();
		}
		
		$forum = $thread->getForum();
		$website = $forum->getWebsite();
		$forums = forums_ForumService::getInstance()->createQuery()->add(Restrictions::published())
			->add(Restrictions::eq('website', $website))
			->add(Restrictions::ne('id', $forum->getId()))
			->find();
		$request->setAttribute('forums', $forums);
		Framework::fatal(__METHOD__ . count($forums));
		
		return 'Chooseforum';
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeMove($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$thread = $this->getDocumentParameter();
		if ($thread === null || !($thread instanceof forums_persistentdocument_thread) || !$thread->isPublished())
		{
			return website_BlockView::NONE;
		}
		
		if (!$this->canModerate($thread))
		{
			$this->addError(LocaleService::getInstance()->trans('m.forums.frontoffice.you-dont-have-permission', array('ucf')));
			return $this->getForbiddenView();
		}
		
		$forum = forums_persistentdocument_forum::getInstanceById($request->getParameter('forumId'));
		$website = $thread->getForum()->getWebsite();
		if ($website != $forum->getWebsite())
		{
			$this->addError(LocaleService::getInstance()->trans('m.forums.frontoffice.invalid-forum', array('ucf')));
			return $this->getForbiddenView();
		}
		$thread->setForum($forum);
		$thread->save();
		
		$this->redirectToUrl(LinkHelper::getDocumentUrl($forum));
		return website_BlockView::NONE;
	}
	
	/**
	 * @param forums_persistentdocument_thread
	 * @return boolean
	 */
	private function canModerate($thread)
	{
		return forums_ModuleService::getInstance()->currentUserHasPermission('modules_forums.Moderate', $thread);
	}
}