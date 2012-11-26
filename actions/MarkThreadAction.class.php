<?php
/**
 * forums_MarkThreadAction
 * @package modules.forums.actions
 */
class forums_MarkThreadAction extends change_Action
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		
		$threadId = $request->getModuleParameter('forums', 'threadId');
		$flag = $request->getModuleParameter('forums', 'thread.flag');
		
		$thread = forums_persistentdocument_thread::getInstanceById($threadId);
		$thread->setFlag($flag);
		$thread->save();
		
		$request->setParameter('location', LinkHelper::getDocumentUrl($thread));
		$request->setParameter('redirectType', 302);
		return change_Controller::getInstance()->forward('website', 'Redirect');
	}
}