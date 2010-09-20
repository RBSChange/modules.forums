<?php
/**
 * forums_BlockNewthreadAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockEditpostAction extends forums_BlockPostListBaseAction
{
	/**
	 * @return array<String, String>
	 */
	public function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_post)
		{
			return array('threadname' => $doc->getThread()->getLabel(), 'forumname' => $doc->getThread()->getForum()->getLabel());
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
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$post = $this->getDocumentParameter();
		if ($post->isEditable())
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
	public function getInputViewName()
	{
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @return Array
	 */
	public function getSubmitInputValidationRules()
	{
		return array_merge(BeanUtils::getBeanValidationRules('forums_persistentdocument_post', null, array('label')), BeanUtils::getSubBeanValidationRules('forums_persistentdocument_post', 'thread', null, null));
	}
	
	public function submitNeedTransaction()
    {
    	return true;
    }
	
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeSubmit($request, $response, forums_persistentdocument_post $post)
	{
		$post->setEditedby(forums_MemberService::getInstance()->getCurrentMember());
		$post->setEditeddate(date_Calendar::now()->toString());
		$post->save();
		if ($post->isFirstPostInThread())
		{
			$post->getThread()->save();
		}
		$tm = f_persistentdocument_TransactionManager::getInstance();		
		while ($tm->hasTransaction())
		{
			$tm->commit();
		}
		$url = $post->getPostUrlInThread();
		HttpController::getInstance()->redirectToUrl($url);
	}
	
	/**
	 * @return Array
	 */
	public function getPreviewInputValidationRules()
	{
		$rules = array_merge(BeanUtils::getBeanValidationRules('forums_persistentdocument_post', null, array('label')), BeanUtils::getSubBeanValidationRules('forums_persistentdocument_post', 'thread', null, null));
		return $rules;
	}
	
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executePreview($request, $response, forums_persistentdocument_post $post)
	{
		if	($post->getAnswerof() !== null && $post->getAnswerof()->getThread()->getId() != $post->getThread()->getId())
		{
			$post->setAnswerof(null);
		}
		$post->setText(website_BBCodeService::getInstance()->fixContent($post->getText()));
		$request->setAttribute('post', $post);
		
		$postListInfo = array();
		$postListInfo['displayConfig'] = $this->getDisplayConfig();
		$postListInfo['paginator'] = array($post);
		$request->setAttribute('previewPostInfo', $postListInfo);
		
		return $this->getInputViewName();
	}
}