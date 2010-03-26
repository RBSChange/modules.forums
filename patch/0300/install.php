<?php
/**
 * forums_patch_0300
 * @package modules.forums
 */
class forums_patch_0300 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$wfs = forums_WebsitefolderService::getInstance();
		foreach (website_WebsiteService::getInstance()->getAll() as $website)
		{
			$wfs->generateForWebsite($website);			
		}
	}

	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'forums';
	}

	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0300';
	}
}