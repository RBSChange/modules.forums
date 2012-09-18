<?php
/**
 * forums_persistentdocument_ban
 * @package modules.forums.persistentdocument
 */
class forums_persistentdocument_ban extends forums_persistentdocument_banbase
{
	/**
	 * @return boolean
	 */
	public function isActive()
	{
		if ($this->getTo())
		{
			return date_Calendar::getInstance($this->getTo())->belongsToFuture();
		}
		return false;
	}
}