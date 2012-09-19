<?php
/**
 * @package modules.forums.setup
 */
class forums_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('init.xml');
		
		// Generate website folders.
		$wfs = forums_WebsitefolderService::getInstance();
		foreach (website_WebsiteService::getInstance()->getAll() as $website)
		{
			$wfs->generateForWebsite($website);			
		}
	}

	/**
	 * @return string[]
	 */
	public function getRequiredPackages()
	{
		return array('modules_users');
	}
}