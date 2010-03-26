<?php
/**
 * forums_TitleService
 * @package forums
 */
class forums_TitleService extends f_persistentdocument_DocumentService
{
	/**
	 * @var forums_TitleService
	 */
	private static $instance;

	/**
	 * @return forums_TitleService
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
		return $this->pp->createQuery('modules_forums/title');
	}
	
	/**
	 * Create a query based on 'modules_forums/title' model.
	 * Only documents that are strictly instance of modules_forums/title
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_forums/title', false);
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
	 * @param forums_persistentdocument_title $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
//	protected function preSave($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
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
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postUpdate($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
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
	 * @param forums_persistentdocument_title $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
//	public function isPublishable($document)
//	{
//		$result = parent::isPublishable($document);
//		return $result;
//	}


	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param forums_persistentdocument_title $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
//	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
//	{
//	}

	/**
	 * Correction document is available via $args['correction'].
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Array<String=>mixed> $args
	 */
//	protected function onCorrectionActivated($document, $args)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param forums_persistentdocument_title $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedTo($fromDocument, $toDocument, $tag)
//	{
//	}

	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
//	protected function onMoveToStart($document, $destId)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
//	}

	/**
	 * this method is call before save the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param f_persistentdocument_PersistentDocument $newDocument
	 * @param f_persistentdocument_PersistentDocument $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//		throw new IllegalOperationException('This document cannot be duplicated.');
//	}

	/**
	 * Returns the URL of the document if has no URL Rewriting rule.
	 *
	 * @param forums_persistentdocument_title $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
//	public function generateUrl($document, $lang, $parameters)
//	{
//	}

	/**
	 * @param forums_persistentdocument_title $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//	}
}