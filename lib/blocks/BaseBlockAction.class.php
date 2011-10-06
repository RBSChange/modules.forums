<?php
/**
 * forums_BaseBlockAction
 * @package modules.forums.lib.blocks
 */
abstract class forums_BaseBlockAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @return TemplateObject
	 */
	protected function getForbiddenView($request)
	{
		$agaviUser = Controller::getInstance()->getContext()->getUser();
		$agaviUser->setAttribute('illegalAccessPage', $_SERVER["REQUEST_URI"]);
		$request->setAttribute('member', forums_MemberService::getInstance()->getCurrentMember());
		return $this->getTemplateByFullName('modules_forums', 'Forums-Block-Generic-Forbidden');
	}
}