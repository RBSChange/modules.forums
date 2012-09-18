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
		forums_ThreadService::getInstance()->sendToFollowers($this->plannedTask);
	}
}