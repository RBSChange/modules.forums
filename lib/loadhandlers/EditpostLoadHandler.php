<?php

class forums_EditpostLoadHandler extends website_ViewLoadHandlerImpl
{
	/**
	 * @see website_ViewLoadHandler::execute()
	 *
	 * @param website_BlockActionRequest $request
	 * @param website_BlockActionResponse $response
	 */
	public function execute($request, $response)
	{
		/* @var $post forums_persistentdocument_post */
		$post = $this->getDocumentParameter();
		
		$request->setAttribute('post', $post);
	}
}