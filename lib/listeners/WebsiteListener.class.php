<?php
class forums_WebsiteListener
{
	public function onPersistentDocumentCreated($sender, $params)
	{
		if ($params['document'] instanceof website_persistentdocument_website)
		{
			forums_WebsitefolderService::getInstance()->generateForWebsite($params['document']);
		}
	}
}