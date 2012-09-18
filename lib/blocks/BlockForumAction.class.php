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
	public function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_forum)
		{
			$label = ($doc->getMetatitle()) ? $doc->getMetatitle() : $doc->getLabel();
			$description = ($doc->getMetadescription()) ? $doc->getMetadescription() : f_util_StringUtils::shortenString(f_util_HtmlUtils::htmlToText($doc->getDescriptionAsHtml()), 100);
			return array('forumname' => $label, 'forumshortdesc' => $description, 'forumkeywords' => $doc->getKeywords());
		}
		return array('forumname' => null, 'forumshortdesc' => null, 'forumkeywords' => null);
	}

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

		$forum = $this->getDocumentParameter();
		if (!($forum instanceof forums_persistentdocument_forum) || !$forum->isPublished())
		{
			return website_BlockView::NONE;
		}
		$request->setAttribute('forum', $forum);
	
		$count = forums_ThreadService::getInstance()->countByForum($forum);
		$page = $request->getParameter('page', 1);
		$itemPerPage = $this->getNbItemPerPage($request, $response);
		$offset = ($page - 1) * $itemPerPage;
		
		$threads = forums_ThreadService::getInstance()->getByForum($forum,$offset, $itemPerPage);
		$paginator = new paginator_Paginator('forums', $page, $threads, $itemPerPage, $count);
				
		$request->setAttribute('paginator', $paginator);
		
		// Global announcements.
		$request->setAttribute('globalAnnoucements', forums_ThreadService::getInstance()->getGlobalAnnoucements());

		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return integer default 10
	 */
	private function getNbItemPerPage($request, $response)
	{
		return $this->getConfiguration()->getNbitemperpage();
	}
}