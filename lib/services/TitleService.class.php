<?php
/**
 * @package modules.forums
 * @method forums_TitleService getInstance()
 */
class forums_TitleService extends f_persistentdocument_DocumentService
{
	/**
	 * @return forums_persistentdocument_title
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_forums/title');
	}

	/**
	 * Create a query based on 'modules_forums/title' model.
	 * Return document that are instance of modules_forums/title,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_forums/title');
	}
	
	/**
	 * Create a query based on 'modules_forums/title' model.
	 * Only documents that are strictly instance of modules_forums/title
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_forums/title', false);
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return forums_persistentdocument_title[]
	 */
	public function getByWebsite($website)
	{
		return $this->createQuery()->add(Restrictions::eq('website', $website))->find();
	}
	
	/**
	 * @return forums_persistentdocument_title[]
	 */
	public function getAll()
	{
		return $this->createQuery()->find();
	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @param integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		// Set website.
		if ($document->getWebsite() === null)
		{
			$wfolder = DocumentHelper::getDocumentInstance($parentNodeId);
			if ($wfolder instanceof forums_persistentdocument_websitefolder)
			{
				$document->setWebsite($wfolder->getWebsite());
			}
			else
			{
				throw new BaseException('Parent must be websitefolder', 'modules.forums.document.title.exception.Parent-must-be-websitefolder');
			}
		}
	}
	
	/**
	 * @param forums_persistentdocument_title $document
	 * @param integer $parentNodeId Parent node ID where to save the document.
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
}