<?php
/**
 * forums_BlockMemberlistAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockMemberlistAction extends website_TaggerBlockAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$members = forums_MemberService::getInstance()->getPublishedMembersByWebsite($website);
		$paginator = new paginator_Paginator('forums', $request->getParameter('page'), $members, $this->getNbItemPerPage($request, $response));

		$request->setAttribute('paginator', $paginator);
		
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return Integer default 10
	 */
	private function getNbItemPerPage($request, $response)
	{
		return $this->getConfiguration()->getNbitemperpage();
	}
}