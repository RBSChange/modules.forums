<?php
class forums_CurrentMemberLoadHandler extends website_ViewLoadHandlerImpl
{
	/**
	 * @see website_ViewLoadHandler::execute()
	 *
	 * @param website_BlockActionRequest $request
	 * @param website_BlockActionResponse $response
	 */
	public function execute($request, $response)
	{
		$member = forums_MemberService::getInstance()->getCurrentMember();
		$request->setAttribute('currentMember', $member);
	}
}