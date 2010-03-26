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
		chdir(WEBEDIT_HOME);
		$cmd = 'php ' . $this->getBatchPath();
		$retVal = null;
		$output = array();
		exec($cmd, $output, $retVal);
		if ("0" != $retVal)
		{
			throw new Exception("Could not execute $cmd (exit code $retVal):\n" . join("", $output));
		}
	}
	
	/**
	 * @return String
	 */
	private function getBatchPath()
	{
		return f_util_FileUtils::buildWebeditPath('modules', 'forums', 'lib', 'bin', 'SendNotificationsToFollowersBatch.php');
	}
}