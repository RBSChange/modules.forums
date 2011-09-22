<?php
class forums_DeleteJSONAction extends generic_DeleteJSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$document = DocumentHelper::getByCorrection($this->getDocumentInstanceFromRequest($request));
		if ($document instanceof forums_persistentdocument_forum)
		{
			$document->getDocumentService()->deleteDelayed($document);
			return $this->sendJSON(array('id' => 0));
		}
		elseif ($document instanceof forums_persistentdocument_member)
		{
			$document->getDocumentService()->deleteDelayed($document, $request->getParameter('deletePosts'));
			return $this->sendJSON(array('id' => 0));
		}
		return parent::_execute($context, $request);
	}
}