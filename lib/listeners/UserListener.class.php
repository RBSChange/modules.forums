<?php
class forums_UserListener
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
	 * @param users_UserService $sender
	 * @param array $params
	 * @return void
	 */
	public function onUserLogin($sender, $params)
	{
		$user = $params['user'];
		if ($user instanceof users_persistentdocument_user)
		{
			$profile = forums_ForumsprofileService::getInstance()->getByAccessorId($user->getId(), true);
			$profile->getDocumentService()->cleanTracking($profile);
			if ($profile->hasMeta('m.forums.sessionStart'))
			{
				$profile->setMeta('m.forums.lastSessionStart', $profile->getMeta('m.forums.sessionStart'));
			}
			$profile->setMeta('m.forums.sessionStart', $user->getLastlogin());
			$profile->saveMeta();
		}
	}
}