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
	function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_thread)
		{
			$post = $doc->getFirstPost();
			$description = f_util_StringUtils::shortenString(f_util_StringUtils::htmlToText($post->getTextAsHtml()), 100);
			return array('threadname' => $doc->getLabel(), 'forumname' => $doc->getForum()->getLabel(), 'threadshortdesc' => $description, 'threadkeywords' => $doc->getKeywords());
		}
		return array();
	}
	
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
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
				
		if ($request->hasParameter('privatenote') || $request->hasNonEmptyParameter('follow') || $request->hasNonEmptyParameter('unfollow'))
		{	
			$tm = f_persistentdocument_TransactionManager::getInstance();
			try
			{
				$tm->beginTransaction();
				if ($request->hasParameter('privatenote') && $thread->isEditable())
				{
					$thread->setPrivatenote($request->getParameter('privatenote'));
					$thread->setPrivatenoteby(forums_MemberService::getInstance()->getCurrentMember());
					$thread->save();
				}
				
				if ($request->hasNonEmptyParameter('follow'))
				{
					$thread->addFollowers(forums_MemberService::getInstance()->getCurrentMember());
					$thread->save();
				}
				if ($request->hasNonEmptyParameter('unfollow'))
				{
					$thread->removeFollowers(forums_MemberService::getInstance()->getCurrentMember());
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
		
		$nbItemPerPage = $thread->getForum()->getNbPostPerPage();
		$page = $request->getParameter('page');
		if (!is_numeric($page) || $page < 1 || $page > ceil($thread->getNbpost() / $nbItemPerPage))
		{
			$page = 1;
		}
		$posts = forums_ThreadService::getInstance()->getPosts($thread, ($nbItemPerPage * ($page - 1)) + 1, $nbItemPerPage);
		$paginator = new paginator_Paginator('forums', $page, $posts, $nbItemPerPage);
		$paginator->setItemCount($thread->getNbpost());
				
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if ($member !== null && count($posts) > 0)
		{
			$member->getDocumentService()->setPostAsReadForMember($member, f_util_ArrayUtils::lastElement($posts));
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
		
		return website_BlockView::SUCCESS;
	}
}