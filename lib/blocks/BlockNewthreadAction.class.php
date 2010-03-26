<?php
/**
 * forums_BlockNewthreadAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockNewthreadAction extends forums_BlockPostListBaseAction
{
	/**
	 * @return array<String, String>
	 */
	function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_forum)
		{
			return array('forumname' => $doc->getLabel());
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
		if ($forum->isWritable())
		{
			return $this->getInputViewName();
		}
		
		$agaviUser = Controller::getInstance()->getContext()->getUser();
		$agaviUser->setAttribute('illegalAccessPage', $_SERVER["REQUEST_URI"]);
		$request->setAttribute('member', forums_MemberService::getInstance()->getCurrentMember());
		return $this->getTemplateByFullName('modules_forums', 'Forums-Block-Generic-Forbidden');
	}
	
	/**
	 * @return String
	 */
	function getInputViewName()
	{
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @return Array
	 */
	public function getSubmitInputValidationRules()
	{
		return array_merge(BeanUtils::getBeanValidationRules('forums_persistentdocument_thread', null, null), BeanUtils::getSubBeanValidationRules('forums_persistentdocument_thread', 'firstPost', null, array('label', 'thread')));
	}
	
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function executeSubmit($request, $response, forums_persistentdocument_thread $thread)
	{
		$post = $thread->getFirstPost();
		$thread->setFirstPost(null);
		$thread->save();
		
		$post->save($thread->getId());
		$post->getDocumentService()->activate($post->getId());
		
		$url = LinkHelper::getDocumentUrl($thread);
		HttpController::getInstance()->redirectToUrl($url);
	}

	/**
	 * @return Array
	 */
	public function getPreviewInputValidationRules()
	{
		return array_merge(BeanUtils::getBeanValidationRules('forums_persistentdocument_thread', null, null), BeanUtils::getSubBeanValidationRules('forums_persistentdocument_thread', 'firstPost', null, array('label', 'thread')));
	}
	
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executePreview($request, $response, forums_persistentdocument_thread $thread)
	{
		$post = $thread->getFirstPost();
		$post->setThread($thread);
		$post->setText(website_BBCodeService::getInstance()->fixContent($post->getText()));
		$post->setPostauthor(forums_MemberService::getInstance()->getCurrentMember());
		$post->setCreationdate(date_Calendar::getInstance()->toString());
		$request->setAttribute('thread', $thread);
		
		$postListInfo = array();
		$postListInfo['displayConfig'] = $this->getDisplayConfig();
		$postListInfo['displayConfig']['hidePostLink'] = true;
		$postListInfo['paginator'] = array($post);
		$request->setAttribute('previewPostInfo', $postListInfo);
		
		return $this->getInputViewName();
	}
}