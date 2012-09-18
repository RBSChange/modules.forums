<?php
/**
 * forums_BlockPostListBaseAction
 * @package modules.forums
 */
abstract class forums_BlockPostListBaseAction extends forums_BaseBlockAction
{
	/**
	 * @var Integer
	 */
	const DEFAULT_ITEMS_PER_PAGE = 20;
	
	/**
	 * @return Array
	 */
	protected function getDisplayConfig()
	{
		$displayConfig = array();
		
		$displayConfig['showGravatars'] = $this->getConfigurationValue('showGravatars', true);
		$displayConfig['avatarsSize'] = $this->getConfigurationValue('avatarsSize', 64);
		$displayConfig['showSignatures'] = $this->getConfigurationValue('showSignatures', true);
		$displayConfig['showActions'] = $this->getConfigurationValue('showActions', false);
		$displayConfig['showPagination'] = $this->getConfigurationValue('showPagination', true);
		$user = users_UserService::getInstance()->getCurrentUser();
		$displayConfig['currentUser'] = $user;
		$profile = ($user) ? forums_ForumsprofileService::getInstance()->getByAccessorId($user->getId(), true) : null;
		$displayConfig['currentProfile'] = $profile;
		
		return $displayConfig;
	}
	
	/**
	 * @return integer
	 */
	protected function getNbItemPerPage()
	{
		$itemsPerPage = $this->getConfigurationValue('nbitemperpage');
		return ($itemsPerPage !== null) ? $itemsPerPage : self::DEFAULT_ITEMS_PER_PAGE;
	}
	
	/**
	 * @param string $name
	 * @param Mixed $defaultValue
	 * @return Mixed
	 */
	protected function getConfigurationValue($name, $defaultValue = null)
	{
		$configuration = $this->getConfiguration();
		$getter = 'get'.ucfirst($name);
		if (f_util_ClassUtils::methodExists($configuration, 'get'.ucfirst($name)))
		{
			return $configuration->{$getter}();
		}
		return $defaultValue;
	}
}