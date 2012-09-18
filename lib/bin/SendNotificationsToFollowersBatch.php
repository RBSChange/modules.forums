<?php
/* @var $arguments array */
$arguments = isset($arguments) ? $arguments : array();
$threadId = $arguments[0];
$rq = RequestContext::getInstance();
$rq->setLang($rq->getDefaultLang());

$ns = notification_NotificationService::getInstance();
$thread = forums_persistentdocument_thread::getInstanceById($threadId);
$ds = $thread->getDocumentService();
$notif = $ns->getConfiguredByCodeName('modules_forums/follower', $ds->getWebsiteId($thread), $thread->getLang());
$num = 1 + ($thread->getNbpost() - $thread->getTofollow()->getNumber());
if ($notif instanceof notification_persistentdocument_notification)
{
	$num = 1 + ($thread->getNbpost() - $thread->getTofollow()->getNumber());
	$lastAuthorId = $thread->getLastPost()->getAuthorid();
	foreach ($thread->getFollowersArray() as $user)
	{
		if ($user->isPublished())
		{
			if ($lastAuthorId != $user->getId())
			{
				$callback = array($ds, 'getNotificationParameters');
				$params = array('thread' => $thread, 'user' => $user, 'specificParams' => array('NUM' => $num));
				$user->getDocumentService()->sendNotificationToUserCallback($notif, $user, $callback, $params);
			}
		}
		else
		{
			$thread->removeFollowers($user);
		}
	}
}
$thread->setTofollow(null);
$thread->save();
echo 'OK';