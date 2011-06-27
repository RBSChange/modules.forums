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
	public function getMotifAsHtml()
	{
		$parser = new website_BBCodeParser();
		return $parser->convertXmlToHtml($this->getMotif());
	}
	
	/**
	 * @return string
	 */
	public function getMotifAsBBCode()
	{
		$parser = new website_BBCodeParser();
		return $parser->convertXmlToBBCode($this->getMotif());
	}

	/**
	 * @param string $bbcode
	 */
	public function setMotifAsBBCode($bbcode)
	{
		$parser = new website_BBCodeParser();
		$this->setMotif($parser->convertBBCodeToXml($bbcode, $parser->getModuleProfile('forums')));
	}
}