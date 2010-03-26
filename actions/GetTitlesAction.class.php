<?php
class forums_GetTitlesAction extends f_action_BaseJSONAction
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute ($context, $request) 
	{
		$folder = $this->getDocumentInstanceFromRequest($request);
		$this->sendJSON($folder->getTitlesInfo());
 	}
	
	/**
	 * @return Boolean by default false
	 */
	protected function isDocumentAction()
	{
		return false;
	}
}