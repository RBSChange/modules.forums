<?php
/**
 * Class where to put your custom methods for document forums_persistentdocument_websitefolder
 * @package forums.persistentdocument
 */
class forums_persistentdocument_websitefolder extends forums_persistentdocument_websitefolderbase 
{
	/**
	 * @see f_persistentdocument_PersistentDocumentImpl::getTreeNodeLabel()
	 * @return String
	 */
	public function getLabel()
	{
		if ($this->getWebsite() !== null)
		{
			return $this->getWebsite()->getLabel();
		}
		return parent::getLabel();
	}
	
	/**
	 * @return array
	 */
	public function getTitlesInfo()
	{
		$data = array();
		foreach (forums_TitleService::getInstance()->getByWebsite($this->getWebsite()) as $title) 
		{
			$info = array(
				'id' => $title->getId(),
				'label' => $title->getLabel()
			);
			$data[] = $info;
		}
		return array('documents' => $data);
	}
	
	/**
	 * @return string
	 */
	public function getTitlesJSON()
	{
		return JsonService::getInstance()->encode($this->getTitlesInfo());
	}
	
	/**
	 * @return array
	 */
	public function getRanksInfo()
	{
		$data = array();
		foreach (forums_RankService::getInstance()->getByWebsite($this->getWebsite()) as $title) 
		{
			$info = array(
				'id' => $title->getId(),
				'label' => $title->getLabel(),
				'thresholdMin' => strval($title->getThresholdMin()),
				'thresholdMax' => ($title->getThresholdMax() == PHP_INT_MAX) ? '' : strval($title->getThresholdMax())
			);
			$data[] = $info;
		}
		return array('documents' => $data);
	}
	
	/**
	 * @return string
	 */
	public function getRanksJSON()
	{
		return JsonService::getInstance()->encode($this->getRanksInfo());
	}
}