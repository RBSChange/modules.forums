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
		
		$post = $this->getDocumentParameter();
		if (!($post instanceof forums_persistentdocument_post) || !$post->isEditable())
		{
			return $this->getForbiddenView();
		}
		
		return $this->getInputViewName();
	}

	/**
	 * @return string
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

	/**
	 * @return boolean 
	 */
	public function submitNeedTransaction()
	{
		return true;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function executeSubmit($request, $response, forums_persistentdocument_post $post)
	{
		if (!$post->isEditable())
		{
			return $this->getForbiddenView();
		}
		
		$post->setEditedby(users_UserService::getInstance()->getCurrentUser());
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
		change_Controller::getInstance()->redirectToUrl($url);
	}
	
	/**
	 * @return Array
	 */
	public function getPreviewInputValidationRules()
	{
		return array_merge(BeanUtils::getBeanValidationRules('forums_persistentdocument_post', null, array('label')), BeanUtils::getSubBeanValidationRules('forums_persistentdocument_post', 'thread', null, null));
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function executePreview($request, $response, forums_persistentdocument_post $post)
	{
		if (!$post->isEditable())
		{
			return $this->getForbiddenView();
		}
		
		if	($post->getAnswerof() !== null && $post->getAnswerof()->getThread()->getId() != $post->getThread()->getId())
		{
			$post->setAnswerof(null);
		}
		$request->setAttribute('post', $post);
		
		$postListInfo = array();
		$postListInfo['displayConfig'] = $this->getDisplayConfig();
		$postListInfo['paginator'] = array($post);
		$request->setAttribute('previewPostInfo', $postListInfo);
		
		return $this->getInputViewName();
	}
}