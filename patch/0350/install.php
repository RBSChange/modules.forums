<?php
/**
 * forums_patch_0350
 * @package modules.forums
 */
class forums_patch_0350 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		$parser = new website_BBCodeParser();
		
		try 
		{
			$tm->beginTransaction();
			foreach (forums_PostService::getInstance()->createQuery()->find() as $doc)
			{
				$text = $doc->getText();
				if (f_util_StringUtils::beginsWith($text, '<div data-profile="'))
				{
					$text = $parser->convertXmlToBBCode($text);
				}
				$doc->setTextAsBBCode($text);
				$pp->updateDocument($doc);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
		}
		
		try 
		{
			$tm->beginTransaction();
			foreach (forums_MemberService::getInstance()->createQuery()->find() as $doc)
			{
				$text = $doc->getSignature();
				if (f_util_StringUtils::beginsWith($text, '<div data-profile="'))
				{
					$text = $parser->convertXmlToBBCode($text);
				}
				$doc->setSignatureAsBBCode($text);
				$pp->updateDocument($doc);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
		}
		
		try 
		{
			$tm->beginTransaction();
			foreach (forums_ThreadService::getInstance()->createQuery()->find() as $doc)
			{
				$text = $doc->getPrivatenote();
				if (f_util_StringUtils::beginsWith($text, '<div data-profile="'))
				{
					$text = $parser->convertXmlToBBCode($text);
				}
				$doc->setPrivatenoteAsBBCode($text);
				$pp->updateDocument($doc);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
		}
		
		try 
		{
			$tm->beginTransaction();
			foreach (forums_BanService::getInstance()->createQuery()->find() as $doc)
			{
				$text = $doc->getMotif();
				if (f_util_StringUtils::beginsWith($text, '<div data-profile="'))
				{
					$text = $parser->convertXmlToBBCode($text);
				}
				$doc->setMotifAsBBCode($text);
				$pp->updateDocument($doc);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
		}
	}
}