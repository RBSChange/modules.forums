<?php

class forums_EditthreadLoadHandler extends website_ViewLoadHandlerImpl
{
	/**
	 * @see website_ViewLoadHandler::execute()
	 *
	 * @param website_BlockActionRequest $request
	 * @param website_BlockActionResponse $response
	 */
	public function execute($request, $response)
	{
		$thread = $this->getDocumentParameter();
		$request->setAttribute('thread', $thread);
	}
}