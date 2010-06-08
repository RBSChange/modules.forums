<?php
if (!defined('WEBEDIT_HOME'))
{
	define('WEBEDIT_HOME', realpath('.'));
	require_once WEBEDIT_HOME . '/framework/Framework.php';
}

Controller::newInstance("controller_ChangeController");
$rq = RequestContext::getInstance();
$rq->setLang($rq->getDefaultLang());
forums_ThreadService::getInstance()->sendToFollowers();