<?php
/**
 * forums_BlockForumAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockForumAction extends website_BlockAction
{
	/**
	 * @return array<String, String>
	 */
	function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_forum)
		{
			$label = ($doc->getMetatitle()) ? $doc->getMetatitle() : $doc->getLabel();
			$description = ($doc->getMetadescription()) ? $doc->getMetadescription() : f_util_StringUtils::shortenString(f_util_StringUtils::htmlToText($doc->getDescriptionAsHtml()), 100);
			return array('forumname' => $label, 'forumshortdesc' => $description, 'forumkeywords' => $doc->getKeywords());
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

		$forum = $this->getDocumentParameter();
		if ($forum === null)
		{
			return website_BlockView::NONE;
		}
		$request->setAttribute('forum', $forum);

		$threads = forums_ThreadService::getInstance()->getByForum($forum);
		$paginator = new paginator_Paginator('forums', $request->getParameter('page', 1), $threads, $this->getNbItemPerPage($request, $response));
		
		$request->setAttribute('paginator', $paginator);
		
		// Global announcements.
		$request->setAttribute('globalAnnoucements', forums_ThreadService::getInstance()->getGlobalAnnoucements());

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
}