<?php
/**
 * @deprecated (will be removed in 4.0) parameters are now set by the block action.
 */
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