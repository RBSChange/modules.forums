<?php
/**
 * forums_patch_0301
 * @package modules.forums
 */
class forums_patch_0301 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/forums/persistentdocument/forum.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'forums', 'forum');
		$newProp = $newModel->getPropertyByName('excludeFromRss');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('forums', 'forum', $newProp);
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
		return '0301';
	}
}