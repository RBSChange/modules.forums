<?php
$threadId = $_POST['argv'][0];
$rq = RequestContext::getInstance();
$rq->setLang($rq->getDefaultLang());

$ns = notification_NotificationService::getInstance();
$thread = forums_persistentdocument_thread::getInstanceById($threadId);
$ds = $thread->getDocumentService();
$notif = $ns->getConfiguredByCodeName('modules_forums/follower', $ds->getWebsiteId($thread), $thread->getLang());
if ($notif instanceof notification_persistentdocument_notification)
{
	$num = 1 + ($thread->getNbpost() - $thread->getTofollow()->getNumber());
	$lastPost = $thread->getLastPost();
	if ($lastPost)
	{
		$lastAuthorId = $lastPost->getAuthorid();
		foreach ($thread->getFollowersArray() as $member)
		{
			$user = $member->getUser();
			if ($user->isPublished())
			{
				if ($lastAuthorId != $user->getId())
				{
					$callback = array($ds, 'getNotificationParameters');
					$params = array('thread' => $thread, 'member' => $member, 'specificParams' => array('NUM' => $num));
					$user->getDocumentService()->sendNotificationToUserCallback($notif, $user, $callback, $params);
				}
			}
			else
			{
				$thread->removeFollowers($member);
			}
		}
	}
}
$thread->setTofollow(null);
$thread->save();
echo 'OK';