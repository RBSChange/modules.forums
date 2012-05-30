<?php
class forums_BackgroundDeleteTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		// Delete forums.
		$ids = forums_ForumService::getInstance()->getIdsToDelete();
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' post to delete: ' . count($ids));		
		}
		$batchPath = 'modules/forums/lib/bin/batchDelete.php';
		foreach (array_chunk($ids, 100) as $chunk)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execScript($batchPath, $chunk);
			// Log fatal errors...
			if ($result != 'OK')
			{
				Framework::error(__METHOD__ . ' ' . $batchPath . ' unexpected result: "' . $result . '"');
			}
		}
		
		// Delete members.
		$ids = forums_MemberService::getInstance()->getIdsToDelete();
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' members to delete: ' . count($ids));		
		}
		$batchPath = 'modules/forums/lib/bin/batchMemberDelete.php';
		foreach (array_chunk($ids, 10) as $chunk)
		{
			do 
			{
				$this->plannedTask->ping();
				$result = f_util_System::execScript($batchPath, $chunk);
				// Log fatal errors...
				if (!f_util_StringUtils::beginsWith($result, 'OK'))
				{
					Framework::error(__METHOD__ . ' ' . $batchPath . ' unexpected result: "' . $result . '"');
					break;
				}
				
				$chunk = (strlen($result) > 3) ? explode(',', substr($result, 3)) : array();
			}
			while (count($chunk) > 0);
		}
	}
}