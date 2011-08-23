<?php
/**
 * forums_RankService
 * @package forums
 */
class forums_RankService extends f_persistentdocument_DocumentService
{
	/**
	 * @var forums_RankService
	 */
	private static $instance;

	/**
	 * @return forums_RankService
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
	 * @return forums_persistentdocument_rank
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_forums/rank');
	}

	/**
	 * Create a query based on 'modules_forums/rank' model.
	 * Return document that are instance of modules_forums/rank,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_forums/rank');
	}
	
	/**
	 * Create a query based on 'modules_forums/rank' model.
	 * Only documents that are strictly instance of modules_forums/rank
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_forums/rank', false);
	}
	
	/**
	 * @param forums_persistentdocument_member $member
	 */
	public function getByMember($member)
	{
		$quantity = $member->getNbpost();
		$query = $this->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('website.id', $member->getUser()->getWebsiteid()))
			->add(Restrictions::le('thresholdMin', $quantity))
			->add(Restrictions::gt('thresholdMax', $quantity))
			->setFirstResult(0)
			->setMaxResults(1);
		return f_util_ArrayUtils::firstElement($query->find());
	}
		
	/**
	 * @param website_persistentdocument_website $website
	 * @return forums_persistentdocument_rank[]
	 */
	public function getByWebsite($website)
	{
		return $this->createQuery()->add(Restrictions::eq('website', $website))->find();
	}
	
	/**
	 * @param forums_persistentdocument_rank $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
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
				throw new BaseException('Parent must be websitefolder', 'modules.forums.document.rank.exception.Parent-must-be-websitefolder');
			}
		}
		
		if ($document->getThresholdMin() === null || $document->getThresholdMin() < 0)
		{
			$document->setThresholdMin(0);
		}
		
		// Update thresholdMax value.
		$query = $this->createQuery()
			->add(Restrictions::eq('website', $document->getWebsite()))
			->add(Restrictions::gt('thresholdMin', $document->getThresholdMin()))
			->add(Restrictions::ne('id', $document->getId()))
			->addOrder(Order::asc('thresholdMin'))
			->setFirstResult(0)
			->setMaxResults(1);
		$otherRank = f_util_ArrayUtils::firstElement($query->find());
		$document->setThresholdMax(($otherRank !== null) ? $otherRank->getThresholdMin() : PHP_INT_MAX);
	}

	/**
	 * @param forums_persistentdocument_rank $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postSave($document, $parentNodeId)
	{
		if ($document->getWebsite() === null)
		{
			$document->delete();
		}
		
		// Update thresholdMax for the rank with a thresholdMin directly inferior.
		$query = $this->createQuery()
			->add(Restrictions::eq('website', $document->getWebsite()))
			->add(Restrictions::lt('thresholdMin', $document->getThresholdMin()))
			->add(Restrictions::gt('thresholdMax', $document->getThresholdMin()));
		$rankToUpdate = $query->findUnique();
		if ($rankToUpdate !== null)
		{
			$rankToUpdate->setThresholdMax($document->getThresholdMin());
			$rankToUpdate->save();
		}
	}
	
	/**
	 * @param abc_persistentdocument_test1 $document
	 * @return void
	 */
	protected function postDelete($document)
	{
		$quantity = $document->getThresholdMin();
		$websiteId = $document->getWebsite()->getId();
		$rank = $this->createQuery()
			->add(Restrictions::eq('website.id', $websiteId))
			->add(Restrictions::eq('thresholdMax', $quantity))
			->findUnique();
		if ($rank !== null)
		{
			$rank->setModificationdate(null);
			$rank->save();
		}
	}
}