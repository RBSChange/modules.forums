<?php
/**
 * @package modules.forums
 * @method forums_BanService getInstance()
 */
class forums_BanService extends f_persistentdocument_DocumentService
{
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
		return $this->getPersistentProvider()->createQuery('modules_forums/ban');
	}
	
	/**
	 * Create a query based on 'modules_forums/ban' model.
	 * Only documents that are strictly instance of modules_forums/ban
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_forums/ban', false);
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @return forums_persistentdocument_ban[]
	 */
	public function getBansForUser($user)
	{
		return $this->createQuery()->add(Restrictions::eq('member', $user))->addOrder(Order::desc('from'))->find();
	}
	
	public function unBanUsers()
	{
		$profiles = forums_ForumsprofileService::getInstance()->createQuery()->add(Restrictions::isNotNull('ban'))->add(Restrictions::le('ban', date_Calendar::now()->toString()))->find();
		foreach ($profiles as $profile)
		{
			$profile->setBan(null);
			$profile->save();
		}
	}
	
	/**
	 * @param forums_persistentdocument_ban $document
	 * @param integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		$document->setFrom(date_Calendar::now()->toString());
	}
	
	/**
	 * @param forums_persistentdocument_ban $document
	 * @param integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		$profile = forums_ForumsprofileService::getInstance()->getByAccessorId($document->getMemberId(), true);
		$profile->setBan($document->getTo());
		$profile->save();
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
	 * @param users_persistentdocument_user $user
	 * @param integer $max the maximum number of bans that can treat
	 * @return integer the number of treated bans
	 */	
	public function treatBansForUserDeletion($user, $max)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::eq('member', $user));
		$query->setFirstResult(0)->setMaxResults($max);
		$count = $query->delete();
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' ' . $count . ' bans treated');
		}
		return $count;
	}
}