<?php
/**
 * forums_BlockEditMemberProfileAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockEditMemberProfileAction extends website_BlockAction
{
	/**
	 * @see website_BlockAction::execute()
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
		
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if ($member === null)
		{
			$user = users_UserService::getInstance()->getCurrentFrontEndUser();
			$member = forums_MemberService::getInstance()->getNewDocumentInstance();
			$member->setUser($user);
			$member->save();
		}
		$request->setAttribute('member', $member);
		
		return website_BlockView::INPUT;
    }
    
    public function saveNeedTransaction()
    {
    	return true;
    }
       
    /**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param users_persistentdocument_websitefrontenduser $user
	 * @return String
	 */
	public function executeSave($request, $response, forums_persistentdocument_member $member)
	{
		$member->save();
		
		$this->addMessage(f_Locale::translate('&modules.users.frontoffice.Informations-updated;'));
		
		return website_BlockView::INPUT;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param users_persistentdocument_websitefrontenduser $user
	 */
	public function validateSaveInput($request, $user)
	{
		$val = BeanUtils::getBeanValidationRules('forums_persistentdocument_member', null, array('label', 'user'));
		return $this->processValidationRules($val, $request, $member);
	}
}