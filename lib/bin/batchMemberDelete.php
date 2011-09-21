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
	$max = 250;
	foreach ($docIdArray as $key => $docId)
	{
		$member = forums_persistentdocument_member::getInstanceById($docId);
		if (Framework::isInfoEnabled())
		{
			Framework::info('Perpare deletion for member ' . $member->getLabel() . ' (' . $member->getId() . ')');
		}
		
		$max = $member->getDocumentService()->prepareMemberDeletion($member, $max);
		if ($max <= 0)
		{
			break;
		}
		
		if (Framework::isInfoEnabled())
		{
			Framework::info('Delete member ' . $member->getLabel() . ' (' . $member->getId() . ')');
		}
		$member->delete();
		unset($docIdArray[$key]);
	}
	$tm->commit();
}
catch (Exception $e)
{
	$tm->rollBack($e);
}
echo 'OK:' . implode(',', $docIdArray);