<?php
class forums_WebsiteBasicStatisticsProducer implements f_chart_DataTableProducer
{
	/**
	 * @param array<String, mixed> $params
	 * @return f_chart_DataTable
	 */
	function getDataTable($params = null)
	{
		$data = new f_chart_DataTable();
				
		$data->addColumn(null, f_chart_DataTable::STRING_TYPE);
		switch ($params['mode'])
		{
			case 'forums' : $data->addColumn(null, f_chart_DataTable::NUMBER_TYPE, '555599'); break;
			case 'threads' : $data->addColumn(null, f_chart_DataTable::NUMBER_TYPE, '559955'); break;
			case 'posts' : $data->addColumn(null, f_chart_DataTable::NUMBER_TYPE, '995555'); break;
			case 'members' : $data->addColumn(null, f_chart_DataTable::NUMBER_TYPE, '995599'); break;
			case 'lastlogin' : $data->addColumn(null, f_chart_DataTable::NUMBER_TYPE, '559999'); break;
			case 'hasposted' : $data->addColumn(null, f_chart_DataTable::NUMBER_TYPE, '999955'); break;
		}
		$data->addRows(7);
				
		$ms = forums_ModuleService::getInstance();
		$website = DocumentHelper::getDocumentInstance($params['websiteId']);
		$fromDate = $toDate = null;
		for ($m = 0 ; $m < 12 ; $m++)
		{
			$this->initPreviousMonthDates($fromDate, $toDate, 11-$m);
			$statistics = $ms->getDashboardMonthStatisticsByWebsite($website, $fromDate, $toDate);
			$data->setValue($m, 0, $statistics['monthShortLabel']);
			switch ($params['mode'])
			{
				case 'forums' : 
				case 'threads' : 
				case 'posts' : 
				case 'members' : 
				case 'lastlogin' : 
				case 'hasposted' :
					$data->setValue($m, 1, $statistics[$params['mode']]);
					break;
			}
		}
		
		return $data;
	}
	
	/**
	 * @param date_Calendar $fromDate
	 * @param date_Calendar $toDate
	 */
	private function initPreviousMonthDates(&$fromDate, &$toDate, $monthCount = 1)
	{
		$fromDate = date_Calendar::now()->sub(date_Calendar::MONTH, $monthCount);
		$fromDate->setDay(1);
		$toDate = date_Calendar::now()->sub(date_Calendar::MONTH, $monthCount);
		$toDate->setDay($toDate->getDaysInMonth());
	}
}