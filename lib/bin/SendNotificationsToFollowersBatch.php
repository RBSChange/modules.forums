<?php
if (!defined("WEBEDIT_HOME"))
{
	define("WEBEDIT_HOME", realpath('.'));
	require_once WEBEDIT_HOME . "/framework/Framework.php";
	$threadId = $_SERVER['argv'][1];
}
else
{
	$threadId = $_POST['argv'][0];
}

Controller::newInstance("controller_ChangeController");
$rq = RequestContext::getInstance();
$rq->setLang($rq->getDefaultLang());

$ns = notification_NotificationService::getInstance();
$notif = $ns->getNotificationByCodeName('modules_forums/follower');
$thread = DocumentHelper::getDocumentInstance($threadId, 'modules_forums/thread');
$num = 1 + ($thread->getNbpost() - $thread->getTofollow()->getNumber());
foreach ($thread->getFollowersArray() as $member)
{
	if ($member->getUser()->isPublished())
	{
		$recipients = new mail_MessageRecipients();
		$recipients->setTo($member->getEmail());
		$replace = array('PSEUDO' => $member->getLabel(), 'TOPIC' => $thread->getLabel(), 'NUM' => $num, 'LINK' => '<a class="link" href="' . $thread->getTofollow()->getPostUrlInThread() . '">' . f_Locale::translate('&modules.forums.frontoffice.thislink;') . '</a>');
		$ns->send($notif, $recipients, $replace, null);
	}
	else
	{
		$thread->removeFollowers($member);
	}
}
$thread->setTofollow(null);
$thread->save();

echo 'OK';