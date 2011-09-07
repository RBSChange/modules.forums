<?php
if (!defined("WEBEDIT_HOME"))
{
	define("WEBEDIT_HOME", realpath('.'));
	require_once WEBEDIT_HOME . "/framework/Framework.php";
	$docIdArray = array_slice($_SERVER['argv'], 1);
}
else
{
	$docIdArray = $_POST['argv'];
}

Controller::newInstance("controller_ChangeController");
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	foreach ($docIdArray as $docId)
	{
		DocumentHelper::getDocumentInstance($docId)->delete();
	}
	$tm->commit();
}
catch (Exception $e)
{
	$tm->rollBack($e);
}
echo 'OK';