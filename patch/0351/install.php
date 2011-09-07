<?php
/**
 * forums_patch_0351
 * @package modules.forums
 */
class forums_patch_0351 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('init.xml');
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'forums';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0351';
	}
}