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
		$member = forums_MemberService::getInstance()->getCurrentMember();
		$ban = forums_persistentdocument_ban::getInstanceById($this->getDocumentIdFromRequest($request));
		if ($member instanceof forums_persistentdocument_member)
		{
			$website = website_WebsiteService::getInstance()->getCurrentWebsite();
			if ($member->isSuperModerator($website))
			{
				$tm = $this->getTransactionManager();
				try
				{
					$tm->beginTransaction();
					$bannedMember = $ban->getMember();
					$bannedMember->setBan(null);
					$bannedMember->save();
					$ban->setTo(date_Calendar::now());
					$ban->save();
					$tm->commit();	
				}
				catch (Exception $e)
				{
					$tm->rollBack($e);
				}
			}
		}
		change_Controller::getInstance()->redirectToUrl(LinkHelper::getDocumentUrl($ban->getMember()));
	}
}