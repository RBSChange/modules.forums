<?php
/**
 * forums_BlockDashboardGeneralStatisticsAction
 * @package modules.forums.lib.blocks
 */
class forums_BlockDashboardGeneralStatisticsAction extends dashboard_BlockDashboardAction
{	
	/**
	 * @see dashboard_BlockDashboardAction::setRequestContent()
	 * @param f_mvc_Request $request
	 * @param boolean $forEdition
	 */
	protected function setRequestContent($request, $forEdition)
	{
		if ($request->hasParameter('websiteId'))
		{
			$website = DocumentHelper::getDocumentInstance($request->getParameter('websiteId'));
		}
		else
		{
			$website = $this->getConfiguration()->getWebsite();
		}
		if ($forEdition || !$website)
		{
			return;
		}
		
		$websiteId = $website->getId();
		$ms = forums_ModuleService::getInstance();
		$request->setAttribute('global', $ms->getDashboardGlobalStatisticsByWebsite($website));
		$configuration = $this->getConfiguration();
		if (!$configuration->getUseCharts())
		{
			$widget = array();
			$fromDate = $toDate = null;
			for ($m = 0 ; $m < 6 ; $m++)
			{
				$this->initPreviousMonthDates($fromDate, $toDate, $m);
				$widget['lines'][] = $ms->getDashboardMonthStatisticsByWebsite($website, $fromDate, $toDate);
			}
			$columns = array();
			foreach (explode(',', $configuration->getColumns()) as $columnName)
			{
				$columns[$columnName] = true;
			}			
			$request->setAttribute('columnsArray', $columns);
			$request->setAttribute('widget', $widget);
		}
		else
		{
			$charts = array();			
			foreach (explode(',', $configuration->getColumns()) as $columnName)
			{
				$producer = new forums_WebsiteBasicStatisticsProducer();
				$chart = new f_chart_BarChart($producer->getDataTable(array('websiteId' => $websiteId, 'mode' => $columnName)));
				$chart->setGrid(new f_chart_Grid(0, 20));
				$charts[] = array('chart' => $chart, 'title' => LocaleService::getInstance()->transBO("m.forums.bo.blocks.dashboardgeneralstatistics.column-$columnName", array('ucf')));
			}			
			$request->setAttribute('charts', $charts);
		}
		$request->setAttribute('websites', website_WebsiteService::getInstance()->getAll());
		$request->setAttribute('websiteId', $websiteId);
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