<?php

class forums_NewthreadLoadHandler extends website_ViewLoadHandlerImpl
{
	/**
	 * @see website_ViewLoadHandler::execute()
	 *
	 * @param website_BlockActionRequest $request
	 * @param website_BlockActionResponse $response
	 */
	public function execute($request, $response)
	{
		$forum = $this->getDocumentParameter();
		$request->setAttribute('forum', $forum);
	}
}