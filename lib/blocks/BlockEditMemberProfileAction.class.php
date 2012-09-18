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
	 * @return string
	 */
	public function execute($request, $response)
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($this->isInBackofficeEdition() || $user === null)
		{
			return website_BlockView::NONE;
		}

		$profile = forums_ForumsprofileService::getInstance()->getByAccessorId($user->getId());
		if ($profile === null)
		{
			$profile = forums_ForumsprofileService::getInstance()->getNewDocumentInstance();
			$profile->setAccessor($user);
		}
		$request->setAttribute('profile', $profile);
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
	 * @param forums_persistentdocument_forumsprofile $profile
	 * @return string
	 */
	public function executeSave($request, $response, forums_persistentdocument_forumsprofile $profile)
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($profile->isNew())
		{
			$profile->setAccessor($user);
		}
		elseif ($user->getId() != $profile->getAccessorId())
		{
			throw new BaseException('Not your profile!', 'm.users.fo.not-your-profile');
		}
		$profile->save();
		$request->setAttribute('profile', $profile);
		RequestContext::getInstance()->resetProfile();
		users_ProfileService::getInstance()->initCurrent(false);
		$this->addMessage(LocaleService::getInstance()->trans('m.users.frontoffice.informations-updated', array('ucf', 'html')));
		return website_BlockView::INPUT;
	}

	/**
	 * @param f_mvc_Request $request
	 * @param forums_persistentdocument_forumsprofile $profile
	 */
	public function validateSaveInput($request, $profile)
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		$profile->setAccessor($user);

		$rules = BeanUtils::getBeanValidationRules('forums_persistentdocument_forumsprofile');
		return $this->processValidationRules($rules, $request, $profile);
	}
}