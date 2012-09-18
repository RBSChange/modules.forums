<?php
/* @var $arguments array */
$arguments = isset($arguments) ? $arguments : array();
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	foreach ($arguments as $docId)
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