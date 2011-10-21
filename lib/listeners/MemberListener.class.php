<?php
class forums_MemberListener
{
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onDayChange($sender, $params)
	{
		forums_BanService::getInstance()->unBanUsers();
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $sender
	 * @param array $params
	 * @return void
	 */
	public function onPersistentDocumentUpdated($sender, $params)
	{
		$document = $params['document'];
		if ($document instanceof users_persistentdocument_user)
		{
			$member = forums_MemberService::getInstance()->getByUser($document, false);
			if ($member !== null)
			{
				$member->getDocumentService()->refreshLabel($member);
				$member->save();
			}
		}
	}
	
	/**
	 * @param users_UserService $sender
	 * @param array $params
	 * @return void
	 */
	public function onUserLogin($sender, $params)
	{
		$user = $params['user'];
		if ($user instanceof users_persistentdocument_user)
		{
			$member = forums_MemberService::getInstance()->getByUser($user, false);
			if ($member !== null)
			{
				$member->getDocumentService()->cleanTracking($member);
				if ($member->hasMeta('m.forums.sessionStart'))
				{
					$member->setMeta('m.forums.lastSessionStart', $member->getMeta('m.forums.sessionStart'));
				}
				$member->setMeta('m.forums.sessionStart', $user->getLastlogin());
				$member->saveMeta();
			}
		}
	}
}