<?php
/**
 * forums_DeleteBanAction
 * @package modules.forums.actions
 */
class forums_DeleteBanAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($user instanceof users_persistentdocument_user && forums_ModuleService::getInstance()->isSuperModerator($user))
		{
			$tm = $this->getTransactionManager();
			try
			{
				$tm->beginTransaction();
				$ban = forums_persistentdocument_ban::getInstanceById($this->getDocumentIdFromRequest($request));
				$profile = $ban->getMember()->getProfile('forums');
				if ($profile) 
				{ 
					$profile->setBan(null);
					$profile->save();
				}
				$ban->setTo(date_Calendar::now());
				$ban->save();
				$tm->commit();	
			}
			catch (Exception $e)
			{
				$tm->rollBack($e);
			}
		}
		change_Controller::getInstance()->redirectToUrl(LinkHelper::getDocumentUrl($ban->getMember()));
	}
}