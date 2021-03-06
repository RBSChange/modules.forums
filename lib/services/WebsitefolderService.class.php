<?php
/**
 * forums_WebsitefolderService
 * @package modules.forums
 */
class forums_WebsitefolderService extends generic_FolderService
{
	/**
	 * @var forums_WebsitefolderService
	 */
	private static $instance;

	/**
	 * @return forums_WebsitefolderService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return forums_persistentdocument_websitefolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_forums/websitefolder');
	}

	/**
	 * Create a query based on 'modules_forums/websitefolder' model.
	 * Return document that are instance of modules_forums/websitefolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_forums/websitefolder');
	}
	
	/**
	 * Create a query based on 'modules_forums/websitefolder' model.
	 * Only documents that are strictly instance of modules_forums/websitefolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_forums/websitefolder', false);
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return forums_persistentdocument_websitefolder
	 */
	public function getByWebsite($website)
	{
		return $this->createQuery()->add(Restrictions::eq('website', $website))->findUnique();
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return forums_persistentdocument_websitefolder
	 */
	public function generateForWebsite($website)
	{
		$folder = $this->getByWebsite($website);
		if ($folder === null)
		{
			$folder = $this->getNewDocumentInstance();
			$folder->setWebsite($website);
			$folder->save(ModuleService::getInstance()->getRootFolderId('forums'));
		}
		return $folder;
	}
	
	/**
	 * @param forums_persistentdocument_websitefolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function postSave($document, $parentNodeId = null)
	{
		parent::postSave($document, $parentNodeId);

		if ($document->getWebsite() === null)
		{
			$document->delete();
		}
	}

	/**
	 * @param forums_persistentdocument_websitefolder $document
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */	
	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
	{
		$nodeAttributes['websiteId'] = $document->getWebsite()->getId();
	}

	/**
	 * @param forums_persistentdocument_websitefolder $document
	 * @return void
	 */
	// TODO: enable cascade deletion?
/*	protected function preDelete($document)
	{
		parent::preDelete($document);
		
		// Delete forums.
		foreach ($this->getChildrenOf($document) as $child)
		{
			$child->delete();
		}
	}*/
}