<?php
class forums_GetTitlesAction extends change_JSONAction
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param change_Context $context
	 * @param change_Request $request
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