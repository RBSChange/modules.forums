<?php
class forums_ListTitlesbywebsiteidService extends BaseService implements list_ListItemsService
{
	/**
	 * @var form_ListRecipientgrouplistService
	 */
	private static $instance;

	/**
	 * @var form_FormService
	 */
	private $parentForm;

	/**
	 * @return form_ListRecipientgrouplistService
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
	 * @param form_FormService $form
	 */
	public final function setParentForm($form)
	{
		$this->parentForm = $form;
	}

	/**
	 * @return array
	 */
	public function getItems()
	{
		try 
		{
			$request = change_Controller::getInstance()->getContext()->getRequest();
			$websiteId = intval($request->getParameter('websiteId', 0));
			$website = DocumentHelper::getDocumentInstance($websiteId);
		}
		catch (Exception $e)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' EXCEPTION: ' . $e->getMessage());
			}
			return array();
		}
		
		$items = array();
		foreach (forums_TitleService::getInstance()->getByWebsite($website) as $title)
		{
			$items[] = new list_Item(
				$title->getLabel(),
				$title->getId()
			);
		}
		return $items;
	}
}