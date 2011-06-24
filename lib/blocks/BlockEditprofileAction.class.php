<?php
/**
 * forums_BlockEditprofileAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockEditprofileAction extends forums_BaseBlockAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
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
		
		$member = $this->getDocumentParameter();
		if ($member === null)
		{
			HttpController::getInstance()->redirect('website', 'Error404');
			return website_BlockView::NONE;
		}
		else if (!$member->isEditable())
		{
			return $this->getForbiddenView();
		}
				
		$request->setAttribute('member', $member);
		return $this->getInputViewName();
	}
	
	/**
	 * @return String
	 */
	public function getInputViewName()
	{
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param forums_persistentdocument_member $member
	 * @return Boolean
	 */
	public function validateSubmitInput($request, forums_persistentdocument_member $member)
	{
		$val = BeanUtils::getBeanValidationRules('forums_persistentdocument_member', null, array('login'));
		$val[] = 'user.email{email:true}';
		$ok = $this->processValidationRules($val, $request, $member);
		if (!users_UserService::getInstance()->checkIdentity($member->getUser(), $request->getParameter('checkpassword')))
		{
			$ok = false;
			$this->addError(LocaleService::getInstance()->transFO('m.forums.frontoffice.passwordwrong', array('ucf')));
		}
		return $ok;
	}
	
	/**
	 * @return boolean
	 */
	public function submitNeedTransaction()
    {
    	return true;
    }
    
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param forums_persistentdocument_member $member
	 */
	public function executeSubmit($request, $response, forums_persistentdocument_member $member)
	{
		if (!$member->isEditable())
		{
			return $this->getForbiddenView();
		}
		
		if ($member->isPropertyModified('email'))
		{
			$fin = date_Calendar::now()->add(date_Calendar::DAY, 2)->toString();
			$member->setEndpublicationdate($fin);
			forums_MemberService::getInstance()->sendReactivationMail($member);
		}
		$member->save();
		$tm = f_persistentdocument_TransactionManager::getInstance();		
		while ($tm->hasTransaction())
		{
			$tm->commit();
		}
		$url = LinkHelper::getDocumentUrl($member);
		$this->redirectToUrl($url);
	}
}