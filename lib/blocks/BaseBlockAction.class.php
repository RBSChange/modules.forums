<?php
/**
 * forums_BaseBlockAction
 * @package modules.forums.lib.blocks
 */
abstract class forums_BaseBlockAction extends website_BlockAction
{
	/**
	 * @return TemplateObject
	 */
	protected function getForbiddenView()
	{
		$agaviUser = change_Controller::getInstance()->getContext()->getUser();
		$agaviUser->setAttribute('illegalAccessPage', $_SERVER["REQUEST_URI"]);
		$this->getRequest()->setAttribute('member', forums_MemberService::getInstance()->getCurrentMember());
		return $this->getTemplateByFullName('modules_forums', 'Forums-Block-Generic-Forbidden');
	}
}