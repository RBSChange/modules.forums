<?php
define("WEBEDIT_HOME", realpath('.'));
require_once WEBEDIT_HOME . '/framework/Framework.php';
if (!file_exists(f_util_FileUtils::buildWebeditPath('site_is_disabled')))
{
	Controller::newInstance("controller_ChangeController");
	$rq = RequestContext::getInstance();
	$rq->setLang($rq->getDefaultLang());
	forums_ThreadService::getInstance()->sendToFollowers();
}
else
{
	Framework::warn('WARNING: Follow thread skipped: '.time()." (site disabled)\n");
}