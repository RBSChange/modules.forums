<?php
/**
 * forums_BlockPostAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockPostAction extends forums_BlockPostListBaseAction
{
	/**
	 * @return array<String, String>
	 */
	function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_post)
		{
			$thread = $doc->getThread();
			$description = f_util_StringUtils::shortenString(f_util_StringUtils::htmlToText($doc->getTextAsHtml()), 100);
			$author = ($doc->getPostauthor() !== null) ? $doc->getPostauthor()->getLabel() : '';
			return array('threadname' => $thread->getLabel(), 'forumname' => $thread->getForum()->getLabel(), 'postauthor' => $author, 'postshortdesc' => $description);
		}
		return array();
	}
	
	/**
	 * @see website_BlockAction::execute()
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
		
		$post = $this->getDocumentParameter();
		if ($post === null || !($post instanceof forums_persistentdocument_post) || !$post->isPublished())
		{
			return website_BlockView::NONE;
		}
		$request->setAttribute('post', $post);
		
		// Post list info.
		$postListInfo = array();
		$postListInfo['displayConfig'] = $this->getDisplayConfig();
		$postListInfo['paginator'] = array($post);
		$request->setAttribute('postListInfo', $postListInfo);
		
		return website_BlockView::SUCCESS;
	}
}