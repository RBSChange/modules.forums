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
	 * @return string
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
		
		$user = users_UserService::getInstance()->getCurrentUser();
		$this->getRequest()->setAttribute('user', $user);
		$profile = ($user) ? forums_ForumsprofileService::getInstance()->getByAccessorId($user->getId(), true) : null;
		$this->getRequest()->setAttribute('profile', $profile);
		
		$handleMemberActions = $this->getConfiguration()->getShowGlobalMemberActions();
		$request->setAttribute('hideMemberActions', !$handleMemberActions);
		$request->setAttribute('currentUrl', LinkHelper::getCurrentUrl());
		
		// Mark all posts as read if asked.
		if ($handleMemberActions && $user !== null && $request->hasParameter('markAllPostsRead'))
		{
			$profile->markAllPostsAsRead();
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
			$parentReference = $parent->getReferenceIdInstance();
			if ($parentReference instanceof forums_persistentdocument_forumgroup)
			{
				$request->setAttribute('forumgroup', $parentReference);
			}
		}
		return forums_ForumgroupService::getInstance()->getByTopicParentId($parent->getId());
	}
}