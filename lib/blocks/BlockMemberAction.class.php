<?php
/**
 * forums_BlockMemberAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockMemberAction extends website_BlockAction
{
	/**
	 * @return array<String, String>
	 */
	public function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_member)
		{
			$date = date_DateFormat::format(date_Calendar::getInstance($doc->getCreationdate()), LocaleService::getInstance()->transFO('m.forums.bo.blocks.date-format-for-metas'));
			return array('login' => $doc->getLabel(), 'registrationdate' => $date);
		}
		return array();
	}

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$member = $this->getDocumentParameter();
		if ($this->isInBackofficeEdition() || !($member instanceof forums_persistentdocument_member))
		{
			return website_BlockView::NONE;
		}

		$request->setAttribute('member', $member);
		if($request->getParameter('resend') && $member->getEndpublicationdate() !== null && $member->isme())
		{
			forums_MemberService::getInstance()->sendReactivationMail($member);
			$request->setAttribute('mailsended', true);
		}
		
		$request->setAttribute('enablePrivateMessaging', $this->enablePrivateMessaging());

		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @return boolean
	 */
	protected function enablePrivateMessaging()
	{
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$tag = 'contextual_website_website_modules_privatemessaging_newthread';
		return TagService::getInstance()->getDocumentByContextualTag($tag, $website, false) != null;
	}
}