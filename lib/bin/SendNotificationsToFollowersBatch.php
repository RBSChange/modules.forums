<?php
$threadId = $arguments[0];
$rq = RequestContext::getInstance();
$rq->setLang($rq->getDefaultLang());

$ns = notification_NotificationService::getInstance();
$thread = DocumentHelper::getDocumentInstance($threadId, 'modules_forums/thread');
$ds = $thread->getDocumentService();
$notif = $ns->getConfiguredByCodeName('modules_forums/follower', $ds->getWebsiteId($thread), $thread->getLang());
$num = 1 + ($thread->getNbpost() - $thread->getTofollow()->getNumber());
if ($notif instanceof notification_persistentdocument_notification)
{
	foreach ($thread->getFollowersArray() as $user)
	{
		if ($user->isPublished())
		{
			$callback = array($ds, 'getNotificationParameters');
			$params = array('thread' => $thread, 'user' => $user, 'specificParams' => array('NUM' => $num));
			$user->getDocumentService()->sendNotificationToUserCallback($notif, $user, $callback, $params);
		}
		else
		{
			$thread->removeFollowers($member);
		}
	}
}
$thread->setTofollow(null);
$thread->save();
echo 'OK';