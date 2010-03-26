<?php
define("WEBEDIT_HOME", realpath('.'));
if (!file_exists(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'webapp' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'site_is_disabled'))
{
	require_once WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'Framework.php';
	
	Controller::newInstance("controller_ChangeController");
	$rq = RequestContext::getInstance();
	$rq->setLang($rq->getDefaultLang());
	forums_ThreadService::getInstance()->sendToFollowers();
}
else
{
	Framework::warn('WARNING: Follow thread skipped: '.time()." (site disabled)\n");
}