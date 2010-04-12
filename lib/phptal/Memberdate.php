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
class PHPTAL_Php_Attribute_CHANGE_memberdate extends ChangeTalAttribute 
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
		$format = self::getFormat($params);
    	return date_DateFormat::format($uiDate, $format);
	}

	/**
	 * @param Array $params
	 * @return String
	 */
	private static function getDateFromParams($params)
	{
		return (array_key_exists('value', $params)) ? $params['value'] : null;
	}
	
	/**
	 * @var Array
	 */
	private static $formats = array();
	
	/**
	 * @param Array $params
	 * @return String
	 */
	private static function getFormat($params)
	{
		$mode = (array_key_exists('mode', $params)) ? $params['mode'] : 'long';
		if (!isset(self::$formats[$mode]))
		{
			$member = forums_MemberService::getInstance()->getCurrentMember();
			$getter = 'get'.ucfirst($mode).'DateFormat';
			$format = null;
			if ($member !== null && f_util_ClassUtils::methodExists($member, $getter))
			{
				$format = f_util_ClassUtils::callMethodOn($member, $getter);
			}
			if (!$format)
			{
				$format = f_Locale::translate('&modules.forums.frontoffice.'.strtolower($mode).'-date-format;');
			}
			self::$formats[$mode] = $format;
		}
		return self::$formats[$mode];
	}
}