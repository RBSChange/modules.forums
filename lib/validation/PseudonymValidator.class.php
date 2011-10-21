<?php
/**
 * This validator validates the label of a forum member.
 */
class validation_PseudonymValidator extends validation_UniqueValidator
{
	/**
	 * @param validation_Property $Field
	 * @param validation_Errors $errors
	 * @return void
	 */
	protected function doValidate(validation_Property $field, validation_Errors $errors)
	{
		if ($this->getParameter() != true)
		{
			return;
		}
			
		// For frontend validation: set the current member.
		// For backend validation, the isValid method already set the document.
		if ($this->document === null)
		{
			$this->document = forums_MemberService::getInstance()->getCurrentMember();
		}
		
		// Validate label unicity by website.		
		$member = forums_MemberService::getInstance()->getByLabel($field->getValue(), $this->getWebsiteId());
		if ($member !== null && !DocumentHelper::equals($member, $this->document))
		{
			$this->reject($field->getName(), $errors);
		}
	}
	
	/**
	 * Returns the error message.
	 * @return string
	 */
	protected function getMessage()
	{
		return LocaleService::getInstance()->transFO('m.forums.frontoffice.label-already-used', array('ucf'));
	}
	
	/**
	 * @return Integer
	 */
	private function getWebsiteId()
	{
		if ($this->getDocumentId() !== null)
		{
			$websiteId = users_UserService::getInstance()->getWebsiteId($this->document->getUser());
			if ($websiteId) {return $websiteId;}
		}
		return website_WebsiteService::getInstance()->getCurrentWebsite()->getId();
	}
	
	/**
	 * @return Integer
	 */
	private function getDocumentId()
	{
		if ($this->document !== null && !$this->document->isNew())
		{
			return $this->document->getId();
		}
		return null;
	}
}