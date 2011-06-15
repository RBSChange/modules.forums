<?php
/**
 * forums_BlockMemberlistAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockMemberlistAction extends website_TaggerBlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}

		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$members = forums_MemberService::getInstance()->getPublishedMembersByWebsite($website);
		$nbItemPerPage = $this->getNbItemPerPage($request, $response);
		$page = $request->getParameter('page');
		if (!is_numeric($page) || $page < 1 || $page > ceil(count($members) / $nbItemPerPage))
		{
			$page = 1;
		}
		$paginator = new paginator_Paginator('forums', $page, $members, $nbItemPerPage);

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