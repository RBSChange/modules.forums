<?php
/**
 * forums_BlockEditMemberProfileAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockEditMemberProfileAction extends forums_BaseBlockAction
{
	/**
	 * @see website_BlockAction::execute()
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
    {
    	$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($this->isInBackofficeEdition() || $user === null)
		{
			return website_BlockView::NONE;
		}
		
		$member = forums_MemberService::getInstance()->getCurrentMember();
		if ($member === null)
		{
			$member = forums_MemberService::getInstance()->getNewDocumentInstance();
			$member->setUser($user);
			$member->save();
		}
		$request->setAttribute('member', $member);		
		return website_BlockView::INPUT;
    }
    
    /**
     * @return boolean
     */
    public function saveNeedTransaction()
    {
    	return true;
    }
       
    /**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param forums_persistentdocument_member $member
	 * @return String
	 */
	public function executeSave($request, $response, forums_persistentdocument_member $member)
	{
		$currentMember = forums_MemberService::getInstance()->getCurrentMember();
		if ($currentMember->getId() !== $member->getId())
		{
			return $this->getForbiddenView();
		}
		
		$member->save();
		
		$this->addMessage(LocaleService::getInstance()->transFO('m.users.frontoffice.informations-updated', array('ucf')));
		
		return website_BlockView::INPUT;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param forums_persistentdocument_member $member
	 */
	public function validateSaveInput($request, $member)
	{
		$val = BeanUtils::getBeanValidationRules('forums_persistentdocument_member', null, array('user'));
		return $this->processValidationRules($val, $request, $member);
	}
}