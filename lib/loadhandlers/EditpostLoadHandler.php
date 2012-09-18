<?php
class forums_EditpostLoadHandler extends website_ViewLoadHandlerImpl
{
	/**
	 * @param website_BlockActionRequest $request
	 * @param website_BlockActionResponse $response
	 */
	public function execute($request, $response)
	{
		$request->setAttribute('post', $this->getDocumentParameter());
	}
}