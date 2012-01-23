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
	public function execute($request, $response)
	{
		if ($this->isInBackofficeEdition())
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
		
		$handleMemberActions = $this->getConfiguration()->getShowGlobalMemberActions();
		$request->setAttribute('hideMemberActions', !$handleMemberActions);
		$request->setAttribute('currentUrl', LinkHelper::getCurrentUrl());
		
		// @deprecated (will be removed in 4.0) use the MarkAllPostRead action instead in your templates.
		if ($handleMemberActions && $member !== null && $request->hasParameter('markAllPostsRead'))
		{
			$member->markAllPostsAsRead();
		}
		
		$request->setAttribute('parent', $this->getContext()->getParent());
		$request->setAttribute('forums', $forums);
		$request->setAttribute('page', $this->getContext()->getPersistentPage());
		
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
		return forums_ForumgroupService::getInstance()->getByTopicParentId($parent->getId());
	}
}