<?php
/**
 * forums_patch_0400
 * @package modules.forums
 */
class forums_patch_0400 extends change_Patch
{
	/**
	 * @return array
	 */
	public function getPreCommandList()
	{
		return array(array('disable-site'));
	}

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Delete old post detail page.
		foreach (TagService::getInstance()->getDocumentsByTag('functional_forums_post-detail') as $doc)
		{
			if (!($doc instanceof website_persistentdocument_pagereference))
			{
				$doc->getDocumentService()->purgeDocument($doc);
			}
		}
		
		// Delete rewriting rule definitions for posts.
		$rule = website_RewriteruleService::getInstance()->getByModelName('modules_forums/member');
		$rule->getDocumentService()->purgeDocument($rule);
	}

	/**
	 * @return array
	 */
	public function getPostCommandList()
	{
		return array(array('clear-documentscache'), array('enable-site'));
	}

	/**
	 * @return string
	 */
	public function getExecutionOrderKey()
	{
		return '2011-09-15 13:31:13';
	}

	/**
	 * @return string
	 */
	public function getBasePath()
	{
		return dirname(__FILE__);
	}

	/**
	 * @return false
	 */
	public function isCodePatch()
	{
		return false;
	}
}