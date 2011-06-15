<?php
/**
 * forums_BlockNewthreadAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockNewpostAction extends forums_BlockPostListBaseAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 */
	public function initialize($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return;
		}

		$ls = LocaleService::getInstance();
		$doc = $this->getDocumentParameter();
		if ($request->hasNonEmptyParameter('postid'))
		{
			$this->getContext()->setNavigationtitle($ls->transFO('m.forums.meta.answerpost', array('ucf')).' '.$doc->getLabel());
		}
		else
		{
			$this->getContext()->setNavigationtitle($ls->transFO('m.forums.meta.newpostin', array('ucf')).' '.$doc->getLabel());
		}
	}
	
	/**
	 * @return array<String, String>
	 */
	public function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_thread)
		{
			return array('threadname' => $doc->getLabel(), 'forumname' => $doc->getForum()->getLabel());
		}
		return array();
	}

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param forums_persistentdocument_post $post
	 * @return String
	 */
	public function execute($request, $response, forums_persistentdocument_post $post)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}

		$thread = $this->getDocumentParameter();
		if (!$thread->isWriteable())
		{
			return $this->getForbiddenView();
		}
		
		if ($request->getParameter('quote') == 'true' && !$request->getParameter('text') && $request->getParameter('postid'))
		{
			$quotedPost = forums_persistentdocument_post::getInstanceById($request->getParameter('postid'));
			$post->setText('[quote="' . $quotedPost->getAuthorNameAsHtml() . '"]' . $quotedPost->getText() . '[/quote]');
			$request->setAttribute('post', $post);
		}
		
		$this->setRequestAttributes($request);			
		return $this->getInputViewName();
	}

	/**
	 * @param f_mvc_Request $request
	 */
	private function setRequestAttributes($request)
	{
		$thread = $this->getDocumentParameter();
		$request->setAttribute('thread', $thread);
		$answerId = $request->getParameter('postid');
		if ($answerId !== null)
		{
			$answerTo = DocumentHelper::getDocumentInstance($answerId);
			$request->setAttribute('answerof', $answerTo);
			$postListInfo = array();
			$postListInfo['displayConfig'] = $this->getDisplayConfig();
			$postListInfo['paginator'] = array($answerTo);
			$request->setAttribute('answerListInfo', $postListInfo);
		}
		else 
		{
			$posts = forums_ThreadService::getInstance()->getPosts($thread, 0, $this->getNbItemPerPage(), 'desc');
			$postListInfo = array();
			$postListInfo['displayConfig'] = $this->getDisplayConfig();
			$postListInfo['paginator'] = $posts;
			$request->setAttribute('lastPostListInfo', $postListInfo);
		}
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
		return BeanUtils::getBeanValidationRules('forums_persistentdocument_post', null, array('label', 'thread'));
	}

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeSubmit($request, $response, forums_persistentdocument_post $post)
	{
		$thread = $post->getThread();
		if (!$thread->isWriteable())
		{
			return $this->getForbiddenView();
		}
		
		if	($post->getAnswerof() !== null && $post->getAnswerof()->getThread()->getId() != $post->getThread()->getId())
		{
			$post->setAnswerof(null);
		}
		$post->save();
		$post->getDocumentService()->activate($post->getId());
						
		$url = $post->getPostUrlInThread();
		HttpController::getInstance()->redirectToUrl($url);
	}
	
	/**
	 * @return Array
	 */
	public function getPreviewInputValidationRules()
	{
		return BeanUtils::getBeanValidationRules('forums_persistentdocument_post', null, array('label', 'thread'));
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executePreview($request, $response, forums_persistentdocument_post $post)
	{
		$thread = $post->getThread();
		if (!$thread->isWriteable())
		{
			return $this->getForbiddenView();
		}
		
		if	($post->getAnswerof() !== null && $post->getAnswerof()->getThread()->getId() != $post->getThread()->getId())
		{
			$post->setAnswerof(null);
		}
		$post->setText(website_BBCodeService::getInstance()->fixContent($post->getText()));
		$post->setPostauthor(forums_MemberService::getInstance()->getCurrentMember());
		$post->setCreationdate(date_Calendar::getInstance()->toString());
		$request->setAttribute('post', $post);
		
		$postListInfo = array();
		$postListInfo['displayConfig'] = $this->getDisplayConfig();
		$postListInfo['displayConfig']['hidePostLink'] = true;
		$postListInfo['paginator'] = array($post);
		$request->setAttribute('previewPostInfo', $postListInfo);
		
		$this->setRequestAttributes($request);
		
		return $this->getInputViewName();
	}
}