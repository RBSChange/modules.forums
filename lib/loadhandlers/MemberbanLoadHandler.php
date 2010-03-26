<?php

class forums_MemberbanLoadHandler extends website_ViewLoadHandlerImpl
{
	/**
	 * @see website_ViewLoadHandler::execute()
	 *
	 * @param website_BlockActionRequest $request
	 * @param website_BlockActionResponse $response
	 */
	public function execute($request, $response)
	{
		$post = $this->getDocumentParameter();
		$request->setAttribute('post', $post);
		$request->setAttribute('bans', forums_BanService::getInstance()->getBansForUser($post->getPostauthor()));
	}
}