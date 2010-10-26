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
		$newPath = f_util_FileUtils::buildWebeditPath('modules/forums/persistentdocument/forumgroup.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'forums', 'forumgroup');
		$newProp = $newModel->getPropertyByName('excludeFromRss');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('forums', 'forumgroup', $newProp);
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