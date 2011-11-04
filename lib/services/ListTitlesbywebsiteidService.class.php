<?php
class forums_ListTitlesbywebsiteidService extends BaseService implements list_ListItemsService
{
	/**
	 * @var forums_ListTitlesbywebsiteidService
	 */
	private static $instance;

	/**
	 * @return forums_ListTitlesbywebsiteidService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * @return array
	 */
	public function getItems()
	{
		$items = array();
		$titles = null;
		try 
		{
			$request = change_Controller::getInstance()->getContext()->getRequest();
			$websiteId = intval($request->getParameter('websiteId', 0));
			if ($websiteId > 0)
			{
				$website = website_persistentdocument_website::getInstanceById($websiteId);
				$titles = forums_TitleService::getInstance()->getByWebsite($website);
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		
		if ($titles === null)
		{
			$titles = forums_TitleService::getInstance()->getAll();
		}
		
		foreach ($titles as $title)
		{
			$items[] = new list_Item(
				$title->getLabel(),
				$title->getId()
			);
		}
		return $items;
	}
}