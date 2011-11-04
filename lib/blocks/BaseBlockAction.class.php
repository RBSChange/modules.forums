<?php
/**
 * forums_BaseBlockAction
 * @package modules.forums.lib.blocks
 */
abstract class forums_BaseBlockAction extends website_BlockAction
{
	/**
	 * @return TemplateObject
	 */
	protected function getForbiddenView()
	{
		change_Controller::getInstance()->getStorage()->writeForUser('users_illegalAccessPage', $_SERVER["REQUEST_URI"]);
		$user = users_UserService::getInstance()->getCurrentUser();
		$this->getRequest()->setAttribute('user', $user);
		$profile = ($user) ? forums_ForumsprofileService::getInstance()->getByAccessorId($user->getId()) : null;
		$this->getRequest()->setAttribute('profile', $profile);
		return $this->getTemplateByFullName('modules_forums', 'Forums-Block-Generic-Forbidden');
	}
}