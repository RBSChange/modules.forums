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
	 * @return array<string>
	 */
	public function getRequiredPackages()
	{
		// Return an array of packages name if the data you are inserting in
		// this file depend on the data of other packages.
		// Example:
		// return array('modules_website', 'modules_users');
		return array('modules_users');
	}
}