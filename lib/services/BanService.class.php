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
			self::$instance = new self();
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
	protected function preInsert($document, $parentNodeId)
	{
		$document->setFrom(date_Calendar::now()->toString());
		$document->setBy(forums_MemberService::getInstance()->getCurrentMember());
	}
	
	/**
	 * @param forums_persistentdocument_ban $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		$member = $document->getMember();
		$member->setBan($document->getTo());
		$member->save();
	}
	
	/**
	 * @param forums_persistentdocument_ban $ban
	 * @return array
	 */
	public function getNotificationParameters($ban)
	{
		$parameters = array();
		$parameters['DATE'] = date_Formatter::toDefaultDate($ban->getUITo());
		$parameters['MOTIF'] = $ban->getMotifAsHtml();		
		$parameters['PSEUDO'] = $ban->getMember()->getLabelAsHtml();
		return $parameters;
	}
	
	/**
	 * @param forums_persistentdocument_member $member
	 * @param integer $max the maximum number of bans that can treat
	 * @return integer the number of treated bans
	 */	
	public function treatBansForMemberDeletion($member, $max)
	{
		$count = 0;
		foreach (array('member', 'by') as $fieldName)
		{
			$query = $this->createQuery();
			$query->add(Restrictions::eq($fieldName, $member));
			$query->setFirstResult(0)->setMaxResults($max - $count);
			$bans = $query->find();
			foreach ($bans as $ban)
			{
				/* @var $ban forums_persistentdocument_ban */
				$ban->getDocumentService()->treatBanForMemberDeletion($ban, $member);
			}
			$count += count($bans);
		}
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' ' . $count . ' bans treated');
		}
		return $count;
	}
	
	/**
	 * @param forums_persistentdocument_ban $ban
	 * @param forums_persistentdocument_member $member
	 */	
	protected function treatBanForMemberDeletion($ban, $member)
	{
		if (DocumentHelper::equals($ban->getMember(), $member))
		{
			$ban->delete();
		}
		elseif (DocumentHelper::equals($ban->getBy(), $member))
		{
			$ban->setBy(null);
			$ban->setMeta('byDeletedMember', $member->getLabel() . ' (' . $member->getId() . ')');
			$ban->save();
		}
	}
}