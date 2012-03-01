<?php
/**
 * forums_patch_0360
 * @package modules.forums
 */
class forums_patch_0360 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/forums/persistentdocument/forumgroup.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'forums', 'forumgroup');
		$newProp = $newModel->getPropertyByName('flagList');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('forums', 'forumgroup', $newProp);
		$this->execChangeCommand('compile-db-schema');
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/forums/persistentdocument/thread.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'forums', 'thread');
		$newProp = $newModel->getPropertyByName('flag');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('forums', 'thread', $newProp);
		
		$this->executeLocalXmlScript("init.xml");
		
	}
}