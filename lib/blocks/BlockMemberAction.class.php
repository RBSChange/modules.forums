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
	function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof forums_persistentdocument_member)
		{
			$date = date_DateFormat::format(date_Calendar::getInstance($doc->getCreationdate()), f_Locale::translate('&modules.forums.bo.blocks.date-format-for-metas;'));
			return array('login' => $doc->getLabel(), 'registrationdate' => $date);
		}
		return array();
	}

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

		$member = $this->getDocumentParameter();
		$request->setAttribute('member', $member);

		if($request->getParameter('resend') && $member->getEndpublicationdate() !== null && $member->isme())
		{
			change_MemberService::getInstance()->sendReactivationMail($member);
			$request->setAttribute('mailsended', true);
		}

		return website_BlockView::SUCCESS;
	}
}