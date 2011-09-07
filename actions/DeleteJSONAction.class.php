<?php
class forums_DeleteJSONAction extends generic_DeleteJSONAction
{
	/* (non-PHPdoc)
	 * @see generic_DeleteJSONAction::_execute()
	 */
	public function _execute($context, $request)
	{
		$document = DocumentHelper::getByCorrection($this->getDocumentInstanceFromRequest($request));
		if ($document instanceof forums_persistentdocument_forum)
		{
			$document->getDocumentService()->deleteDelayed($document);
			return $this->sendJSON(array('id' => 0));
		}
		return parent::_execute($context, $request);
	}
}