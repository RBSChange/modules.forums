<?php
/**
 * forums_BlockNewthreadAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockNewpostAction extends forums_BlockPostListBaseAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function initialize($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}

		$doc = $this->getDocumentParameter();
		if ($request->hasNonEmptyParameter('postid'))
		{
			$this->getContext()->setNavigationtitle(f_Locale::translate('&modules.forums.meta.Answerpost;').' '.$doc->getLabel());
		}
		else
		{
			$this->getContext()->setNavigationtitle(f_Locale::translate('&modules.forums.meta.Newpostin;').' '.$doc->getLabel());
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

		$thread = $this->getDocumentParameter();
		if ($thread->isWriteable())
		{
			$this->setRequestAttributes($request);			
			return $this->getInputViewName();
		}
		
		$agaviUser = Controller::getInstance()->getContext()->getUser();
		$agaviUser->setAttribute('illegalAccessPage', $_SERVER["REQUEST_URI"]);
		$request->setAttribute('member', forums_MemberService::getInstance()->getCurrentMember());
		return $this->getTemplateByFullName('modules_forums', 'Forums-Block-Generic-Forbidden');
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
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeSubmit($request, $response, forums_persistentdocument_post $post)
	{
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