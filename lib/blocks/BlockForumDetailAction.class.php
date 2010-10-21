<?php
/**
 * forums_BlockForumDetailAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockForumDetailAction extends website_BlockAction
{
	/**
	 * @return array<String, String>
	 */
	function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_forumgroup)
		{
			$label = ($doc->getMetatitle()) ? $doc->getMetatitle() : $doc->getLabel();
			$description = ($doc->getMetadescription()) ? $doc->getMetadescription() : f_util_StringUtils::shortenString(f_util_StringUtils::htmlToText($doc->getDescriptionAsHtml()), 100);
			return array('forumname' => $label, 'forumshortdesc' => $description, 'forumkeywords' => $doc->getKeywords());
		}
		return array('forumname' => null, 'forumshortdesc' => null, 'forumkeywords' => null);
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
		
		$parent = $this->getContext()->getParent();
		$request->setAttribute('parent', $parent);
		$forumgroup = $this->getForumgroup($parent);
		$request->setAttribute('forumgroup', $forumgroup);
		$forums = forums_ForumService::getInstance()->getByTopicParentId($parent->getId());
		$request->setAttribute('forums', $forums);
		
		if ($forumgroup instanceof forums_peristentdocument_forum)
		{
			$threads = forums_ThreadService::getInstance()->getByForum($forumgroup);
			$paginator = new paginator_Paginator('forums', $request->getParameter('page', 1), $threads, $this->getNbItemPerPage($request, $response));
			$request->setAttribute('threadsPaginator', $paginator);
			
			// Global announcements.
			$request->setAttribute('globalAnnoucements', forums_ThreadService::getInstance()->getGlobalAnnoucements());
		}

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
	 * @param f_persistentdocument_PersistentDocument $parent
	 * @return forums_persistentdocument_forumgroup
	 */
	private function getForumgroup($parent)
	{
		$forumgroup = $this->getDocumentParameter();
		if ($forumgroup instanceof forums_persistentdocument_forumgroup)
		{
			return $forumgroup;
		}
		
		if ($parent instanceof website_persistentdocument_systemtopic)
		{
			$parentReference = $parent->getReference();
			if ($parentReference instanceof forums_persistentdocument_forumgroup)
			{
				return $forumgroup;
			}
		}
		return null;
	}
}