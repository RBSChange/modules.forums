<?php
/**
 * forums_persistentdocument_ban
 * @package modules.forums.persistentdocument
 */
class forums_persistentdocument_ban extends forums_persistentdocument_banbase
{
	/**
	 * @return String
	 */
	public function display()
	{
		return website_BBCodeService::getInstance()->toHtml($this->getMotif());
	}
}