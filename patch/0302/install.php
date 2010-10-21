<?php
/**
 * forums_patch_0302
 * @package modules.forums
 */
class forums_patch_0302 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('lists.xml');
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/forums/persistentdocument/forum.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'forums', 'forum');
		$newProp = $newModel->getPropertyByName('excludeFromRssMode');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('forums', 'forumgroup', $newProp);
		$newProp = $newModel->getPropertyByName('lockedMode');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('forums', 'forumgroup', $newProp);
		
		$forums = forums_ForumService::getInstance()->createQuery()->find();
		$forumsByMountParentId = array();
		foreach ($forums as $forum)
		{
			$parentId = $forum->getMountParent()->getId();
			if (!isset($forumsByMountParentId[$parentId]))
			{
				$forumsByMountParentId[$parentId] = array();
			}
			$forumsByMountParentId[$parentId][] = $forum;
		}
		
		$sts = website_SystemtopicService::getInstance();
		$fgs = forums_ForumgroupService::getInstance();
		$wfs = forums_WebsitefolderService::getInstance();
		try 
		{
			$this->beginTransaction();
			foreach ($forumsByMountParentId as $parentId => $forums)
			{
				$parent = DocumentHelper::getDocumentInstance($parentId);
				$forumGroup = $fgs->getNewDocumentInstance();
				$website = f_util_ArrayUtils::firstElement($forums)->getWebsite();
				$wf = $wfs->getByWebsite($website);
				if ($parent instanceof website_persistentdocument_topic)
				{
					$forumGroup->setLabel($parent->getLabel());
					$parent->deactivate();
					$systemTopic = $this->getPersistentProvider()->mutate($parent, $sts->getNewDocumentInstance());
					$systemTopic->setReferenceId($forumGroup->getId());
					$forumGroup->setTopic($systemTopic);
					$forumGroup->setWebsite($website);
					$mountParent = $parent->getDocumentService()->getParentOf($parent);
					$forumGroup->setMountParent($mountParent);
					$forumGroup->save($wf->getId());
					$systemTopic->save();
				}
				else 
				{
					$forumGroup->setLabel('Forums');
					$forumGroup->setMountParent($parent);
					$forumGroup->save($wf->getId());
				}
				$forumGroup->activate();
				
				foreach ($forums as $forum)
				{
					$forum->getDocumentService()->moveTo($forum, $forumGroup->getId());
					$forum->setLockedMode($forum->getLocked() ? 1 : 0);
					$forum->setExcludeFromRssMode($forum->getExcludeFromRss() ? 1 : 0);
					$forum->save();
				}
			}
			$this->commit();
		}
		catch (Exception $e)
		{
			$this->rollBack($e);
		}
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
		return '0302';
	}
}