<?php
/**
 * forums_BanService
 * @package modules.forums
 */
class forums_BanService extends f_persistentdocument_DocumentService
{
	/**
	 * @var forums_BanService
	 */
	private static $instance;
	
	/**
	 * @return forums_BanService
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
	 * @return forums_persistentdocument_ban
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_forums/ban');
	}
	
	/**
	 * Create a query based on 'modules_forums/ban' model.
	 * Return document that are instance of modules_forums/ban,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_forums/ban');
	}
	
	/**
	 * Create a query based on 'modules_forums/ban' model.
	 * Only documents that are strictly instance of modules_forums/ban
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_forums/ban', false);
	}
	
	/**
	 * @param forums_persistentdocument_member $member
	 * @return Array<forums_persistentdocument_ban>
	 */
	public function getBansForUser($member)
	{
		return $this->createQuery()->add(Restrictions::eq('member', $member->getId()))->addOrder(Order::desc('from'))->find();
	}
	
	public function unBanUsers()
	{
		$users = forums_MemberService::getInstance()->createQuery()->add(Restrictions::isNotNull('ban'))->add(Restrictions::le('ban', date_Calendar::now()->toString()))->find();
		foreach ($users as $user)
		{
			$user->setBan(null);
			$user->save();
		}
	}
	
	/**
	 * @param forums_persistentdocument_ban $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		$document->setFrom(date_Calendar::now()->toString());
		$document->setBy(forums_MemberService::getInstance()->getCurrentMember());
	}
	
	/**
	 * @param forums_persistentdocument_ban $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId = null)
	{
		$member = $document->getMember();
		$member->setBan($document->getTo());
		$member->save();
	}
}