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
			$this->getContext()->setNavigationtitle($ls->transFO('m.forums.meta.answerpost', array('ucf')) . ' ' . $doc->getLabel());
		}
		else
		{
			$this->getContext()->setNavigationtitle($ls->transFO('m.forums.meta.newpostin', array('ucf')) . ' ' . $doc->getLabel());
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
		if ($this->isInBackofficeEdition())
		{
			return website_BlockView::NONE;
		}
		elseif ($this->isInBackofficePreview())
		{
			return $this->getInputViewName();
		}
		$thread = $this->getDocumentParameter();
		if ($thread === null)
		{
			return website_BlockView::NONE;
		}
		if (!$thread->isWriteable())
		{
			return $this->getForbiddenView();
		}
		if ($request->getParameter('quote') == 'true' && !$request->getParameter('text') && $request->getParameter('postid'))
		{
			$quotedPost = forums_persistentdocument_post::getInstanceById($request->getParameter('postid'));
			$post->setTextAsBBCode('[quote="' . $quotedPost->getAuthorNameAsHtml() . '"]' . $quotedPost->getTextAsBBCode() . '[/quote]');
			$request->setAttribute('post', $post);
		}
		$this->setRequestAttributes($request);
		return $this->getInputViewName();
	}
	
	/**
	 * @param f_mvc_Request $request
	 */
	public function onValidateInputFailed($request)
	{
		$this->setRequestAttributes($request);
	}
	
	/**
	 * @param f_mvc_Request $request
	 */
	private function setRequestAttributes($request)
	{
		/* @var $thread forums_persistentdocument_thread */
		$thread = $this->getDocumentParameter();
		if ($request->hasParameter('thread.flag'))
		{
			$thread->setFlag($request->getParameter('thread.flag'));
		}
		elseif (!$request->hasParameter('post') && !$request->hasAttribute('post'))
		{
			$post = forums_persistentdocument_post::getNewInstance();
			$post->setThread($thread);
			$request->setAttribute('post', $post);
		}
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
	 * @param forums_persistentdocument_post $post
	 * @return String
	 */
	public function executeSubmit($request, $response, forums_persistentdocument_post $post)
	{
		$thread = $post->getThread();
		if (!$thread->isWriteable())
		{
			return $this->getForbiddenView();
		}
		
		if ($post->getAnswerof() !== null && $post->getAnswerof()->getThread()->getId() != $post->getThread()->getId())
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
	 * @return string[]
	 */
	public function getPostBeanInclude()
	{
		if (Framework::getConfigurationValue('modules/website/useBeanPopulateStrictMode') != 'false')
		{
			$include = array('textAsBBCode', 'thread', 'answerof');

			if ($this->getRequest()->hasParameter('thread'))
			{
				$threadId = $this->getRequest()->getParameter('thread');
			}
			else
			{
				$threadId = $this->getRequest()->getParameter('cmpref');
			}

			/* @var $thread forums_persistentdocument_thread */
			$thread = DocumentHelper::getDocumentInstance($threadId);
			if ($thread->isEditable())
			{
				$include[] = 'thread.flag';
			}
			if ($thread->canModerate())
			{
				$include[] = 'thread.level';
			}
			return $include;
		}
		return null;
	}

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param forums_persistentdocument_post $post
	 * @return String
	 */
	public function executePreview($request, $response, forums_persistentdocument_post $post)
	{
		$thread = $post->getThread();
		if (!$thread->isWriteable() || ($post->getAnswerof() && $post->getAnswerof()->isModified()))
		{
			return $this->getForbiddenView();
		}
		
		if ($post->getAnswerof() !== null && $post->getAnswerof()->getThread()->getId() != $post->getThread()->getId())
		{
			$post->setAnswerof(null);
		}
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