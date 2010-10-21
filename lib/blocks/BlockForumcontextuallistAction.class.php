<?php
/**
 * forums_BlockForumcontextuallistAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockForumcontextuallistAction extends website_BlockAction
{
	/**
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
		
		$forums = $this->getDocumentList($request, $response);
		if (count($forums) < 1)
		{
			return website_BlockView::NONE;
		}
		
		$member = forums_MemberService::getInstance()->getCurrentMember();
		$request->setAttribute('member', $member);
		
		// Mark all posts as read if asked.
		if ($member !== null && $request->hasParameter('markAllPostsRead'))
		{
			$member->markAllPostsAsRead();
		}

		$request->setAttribute('parent', $this->getContext()->getParent());
		$request->setAttribute('forums', $forums);
		$request->setAttribute('page', $this->getContext()->getPersistentPage());
		
		// @deprecated This paginator will be removed in 3.5.
		$paginator = new paginator_Paginator('forums',
			$request->getParameter(paginator_Paginator::REQUEST_PARAMETER_NAME, 1),
			$forums,
			1000
		);
		$request->setAttribute('paginator', $paginator);
		
		return website_BlockView::SUCCESS;
	}

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	private function getDocumentList($request, $response)
	{
		$parent = $this->getContext()->getParent();
		$request->setAttribute('parent', $parent);
		if ($parent instanceof website_persistentdocument_systemtopic)
		{
			$parentReference = $parent->getReference();
			if ($parentReference instanceof forums_persistentdocument_forumgroup)
			{
				$request->setAttribute('forumgroup', $parentReference);
			}
		}
		return forums_ForumService::getInstance()->getByTopicParentId($parent->getId());
	}
}