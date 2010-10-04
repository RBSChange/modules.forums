<?php
/**
 * @author intportg
 * @package modules.forums
 */
class forums_SendNotificationsToFollowersTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$batchPath = 'modules/forums/lib/bin/SendNotificationsToFollowersBatch.php';
		$result = f_util_System::execHTTPScript($batchPath);
		// Log fatal errors...
		if ($result != '1')
		{
			Framework::error(__METHOD__ . ' ' . $batchPath . ' an error occured: "' . $result . '"');
		}
	}
}