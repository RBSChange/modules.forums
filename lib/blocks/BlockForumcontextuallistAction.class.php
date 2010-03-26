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
		
		$member = forums_MemberService::getInstance()->getCurrentMember();
		$request->setAttribute('member', $member);
		
		// Mark all posts as read if asked.
		if ($member !== null && $request->hasParameter('markAllPostsRead'))
		{
			$member->markAllPostsAsRead();
		}

		// Set the paginator.
		$paginator = new paginator_Paginator('forums',
			$request->getParameter(paginator_Paginator::REQUEST_PARAMETER_NAME, 1),
			$this->getDocumentList($request, $response),
			$this->getNbItemPerPage($request, $response)
		);

		$request->setAttribute('parent', $this->getContext()->getParent());
		$request->setAttribute('paginator', $paginator);
		$request->setAttribute('page', $this->getContext()->getPersistentPage());
		
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return Integer default 10
	 */
	private function getNbItemPerPage($request, $response)
	{
		return $this->getConfiguration()->getNbitemperpage();
	}

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	private function getDocumentList($request, $response)
	{
		// Get the parent document instance.
        $parent = $this->getContext()->getParent();
		$request->setAttribute('parent', $parent);
		return forums_ForumService::getInstance()->getByTopicParentId($parent->getId());
	}
}