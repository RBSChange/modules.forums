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
			$result = f_util_System::execHTTPScript($batchPath, $chunk);
			// Log fatal errors...
			if ($result != 'OK')
			{
				Framework::error(__METHOD__ . ' ' . $batchPath . ' unexpected result: "' . $result . '"');
			}
		}
	}
}