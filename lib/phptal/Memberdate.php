<?php
// change:memberdate
//
// Default mode (long):
//   <span change:memberdate="page/getStartpublicationdate" />
// Specify mode:
//   <span change:memberdate="mode 'short'; value page/getStartpublicationdate" />

/**
 * @package forums.list.phptal
 */
class PHPTAL_Php_Attribute_CHANGE_Memberdate extends ChangeTalAttribute 
{	
	/**
	 * @see ChangeTalAttribute::getDefaultParameterName()
	 *
	 * @return String
	 */
	protected function getDefaultParameterName()
	{
		return 'value';
	}
	
	/**
	 * @see ChangeTalAttribute::getEvaluatedParameters()
	 *
	 * @return array
	 */
	public function getEvaluatedParameters()
	{
		return array('mode', 'value');
	}
	
	/**
	 * @param Array $params
	 * @return String
	 */
	public static function renderMemberdate($params)
	{
		$date = date_Calendar::getInstance(self::getDateFromParams($params));		
		$uiDate = date_Converter::convertDateToLocal($date);
		$mode = (array_key_exists('mode', $params)) ? $params['mode'] : 'long';
		if ($mode === 'long')
		{
			return date_Formatter::toDefaultDateTime($uiDate);
		}
    	return date_Formatter::toDefaultDate($uiDate);
	}

	/**
	 * @param Array $params
	 * @return String
	 */
	private static function getDateFromParams($params)
	{
		return (array_key_exists('value', $params)) ? $params['value'] : null;
	}
}