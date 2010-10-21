<?php
/**
 * forums_BlockRssMenuAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockRssMenuAction extends website_BlockAction
{
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
		
		$doc = $this->getDocumentParameter('parentref');
		if ($doc instanceof forums_persistentdocument_post)
		{
			$thread = $doc->getThread();
			$forum = $thread->getForum();
			$topic = null;
			$website = $forum->getWebsite();
		}
		else if ($doc instanceof forums_persistentdocument_thread)
		{
			$thread = $doc;
			$forum = $thread->getForum();
			$topic = null;
			$website = $forum->getWebsite();
		}
		else if ($doc instanceof forums_persistentdocument_forumgroup)
		{
			$thread = null;
			$forum = $doc;
			$topic = null;
			$website = $forum->getWebsite();
		}
		else if ($doc instanceof website_persistentdocument_topic)
		{
			$thread = null;
			$forum = null;
			$topic = $doc;
			$website = f_util_ArrayUtils::firstElement($topic->getDocumentService()->getAncestorsOf($topic, 'modules_website/website'));
		}
		else if ($doc instanceof website_persistentdocument_website)
		{
			$thread = null;
			$forum = null;
			$topic = null;
			$website = $doc;
		}
		
		// Handle forum exclusion.
		if ($forum instanceof forums_persistentdocument_forumgroup && $forum->isExcludedFromRss())
		{
			return website_BlockView::NONE;
		}
		
		// Add RSS feeds.
		$this->addRssFeed($thread, 'false', array('post'));
		$this->addRssFeed($forum, 'false');
		$this->addRssFeed($topic, 'true');
		$this->addRssFeed($website, 'true');
		
		$request->setAttribute('links', $this->links);
		
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @var String[]
	 */
	private $links = array();
	
	/**
	 * @param f_persistentdocument_PersistentDocument $parent
	 * @param Boolean $recursive
	 * @param String[] $forTypes
	 */
	private function addRssFeed($parent, $recursive, $forTypes = array('post', 'thread'))
	{
		if ($parent !== null)
		{
			$modelName = f_Locale::translate($parent->getPersistentModel()->getDocumentName());
			foreach ($forTypes as $type)
			{
				$title = f_Locale::translate('&modules.forums.frontoffice.' . ucfirst($type) . 's-of-' . $modelName . 'Label;') . ' ' . $parent->getLabelAsHtml();
				$this->getContext()->addRssFeed($title, LinkHelper::getActionUrl('forums', 'ViewFeed', array('parentref' => $parent->getId(), 'docType' => 'post')));
				$this->links[] = array('title' => $title, 'parentref' => $parent->getId(), 'docType' => $type, 'recursive' => $recursive);
			}
		}
	}
}