<?php
/**
 * forums_patch_0350
 * @package modules.forums
 */
class forums_patch_0350 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		
		try 
		{
			$tm->beginTransaction();
			foreach (forums_PostService::getInstance()->createQuery()->find() as $post)
			{
				$post->setTextAsBBCode($post->getText());
				$pp->updateDocument($post);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
		}
		
		try 
		{
			$tm->beginTransaction();
			foreach (forums_MemberService::getInstance()->createQuery()->find() as $member)
			{
				$member->setSignatureAsBBCode($member->getSignature());
				$pp->updateDocument($member);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
		}
		
		try 
		{
			$tm->beginTransaction();
			foreach (forums_ThreadService::getInstance()->createQuery()->find() as $thread)
			{
				$thread->setPrivatenoteAsBBCode($thread->getPrivatenote());
				$pp->updateDocument($thread);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
		}
		
		try 
		{
			$tm->beginTransaction();
			foreach (forums_BanService::getInstance()->createQuery()->find() as $ban)
			{
				$ban->setMotifAsBBCode($ban->getMotif());
				$pp->updateDocument($ban);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
		}
	}
}