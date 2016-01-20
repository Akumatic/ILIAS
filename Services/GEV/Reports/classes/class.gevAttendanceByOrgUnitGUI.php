<?php
/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */#

/**
* Report "Attendance By OrgUnit"
* for Generali
*
* @author	Nils Haagen <nhaagen@concepts-and-training.de>
* @version	$Id$
*
*
*/

ini_set("memory_limit","2048M"); 
ini_set('max_execution_time', 0);
set_time_limit(0);


require_once("Services/Calendar/classes/class.ilDatePresentation.php");
require_once("Services/GEV/Reports/classes/class.catBasicReportGUI.php");
require_once("Services/CaTUIComponents/classes/class.catTitleGUI.php");
require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
require_once("Modules/OrgUnit/classes/class.ilObjOrgUnit.php");
require_once("Services/GEV/Utils/classes/class.gevObjectUtils.php");
require_once("Services/GEV/Utils/classes/class.gevOrgUnitUtils.php");


class gevAttendanceByOrgUnitGUI extends catBasicReportGUI{
	protected $orgu_membeships;
	protected $dates;
	public function __construct() {
		
		parent::__construct();
		$this->checkPermissionOnTarget();

		$this->title = catTitleGUI::create()
						->title("gev_rep_attendance_by_orgunit_title")
						->subTitle("gev_rep_attendance_by_orgunit_desc")
						->image("GEV_img/ico-head-edubio.png")
						;

		$this->table = catReportTable::create()
						->column("orgu_title", "title")
						->column("odbd", "gev_od_bd")
						//->column("above2", "above2")
						//->column("above1", "above1")
						->column("sum_employees", "sum_employees")
						
						->column("sum_booked_wbt", "sum_booked_WBT")
						->column("sum_attended_wbt", "sum_attended_WBT")
						
						->column("sum_booked", "sum_booked_nowbt")
						->column("sum_waiting", "sum_waiting")
						->column("sum_attended", "sum_attended_nowbt")
						->column("sum_excused", "sum_excused")
						->column("sum_unexcused", "sum_unexcused")
						->column("sum_exit", "sum_exit")
						
						->template("tpl.gev_attendance_by_orgunit_row.html", "Services/GEV/Reports")
						;

		$this->table_sums = catReportTable::create()
						->column("sum_employees", "sum_employees")
						->column("sum_booked_wbt", "sum_booked_WBT")
						->column("sum_attended_wbt", "sum_attended_WBT")
						->column("sum_booked", "sum_booked_nowbt")
						->column("sum_waiting", "sum_waiting")
						->column("sum_attended", "sum_attended_nowbt")
						->column("sum_excused", "sum_excused")
						->column("sum_unexcused", "sum_unexcused")
						->column("sum_exit", "sum_exit")

						->template("tpl.gev_attendance_by_orgunit_sums_row.html", "Services/GEV/Reports")
						;
		$this->summed_data = array();


		

		$this->order = catReportOrder::create($this->table)
						//->mapping("date", "crs.begin_date")
						//->mapping("odbd", array("org_unit_above1", "org_unit_above2"))
						->defaultOrder("orgu_title", "ASC")
						;
		
		//internal ordering:
		$this->internal_sorting_numeric = array(
			'sum_employees'
		);
		$this->internal_sorting_fields = array_merge(
			$this->internal_sorting_numeric,
			array(
		 	  'odbd'
			));

		$this->allowed_user_ids = $this->user_utils->getEmployees();

		$this->filter = catFilter::create()
		
						->dateperiod( "period"
									, $this->lng->txt("gev_period")
									, $this->lng->txt("gev_until")
									, "usrcrs.begin_date"
									, "usrcrs.end_date"
									, date("Y")."-01-01"
									, date("Y")."-12-31"
									, false
									," OR TRUE"
									);
		$orgu_filter = new recursiveOrguFilter('org_unit', 'orgu.orgu_id', true, true);
		$orgu_filter->setFilterOptionsByUser($this->user_utils);
		$orgu_filter->addToFilter($this->filter);

		$this->filter		->multiselect("edu_program"
									 , $this->lng->txt("gev_edu_program")
									 , "edu_program"
									 , gevCourseUtils::getEduProgramsFromHisto()
									 , array()
									 , ""
									 , 200
									 , 160	
									 )
						->multiselect("type"
									 , $this->lng->txt("gev_course_type")
									 , "type"
									 , gevCourseUtils::getLearningTypesFromHisto()
									 , array()
									 , ""
									 , 200
									 , 160	
									 )
						->multiselect("template_title"
									 , $this->lng->txt("crs_title")
									 , "template_title"
									 , gevCourseUtils::getTemplateTitleFromHisto()
									 , array()
									 , ""
									 , 300
									 , 160	
									 )
						->multiselect("participation_status"
									 , $this->lng->txt("gev_participation_status")
									 , "participation_status"
									 , array(	"teilgenommen"=>"teilgenommen"
									 			,"fehlt ohne Absage"=>"fehlt ohne Absage"
									 			,"fehlt entschuldigt"=>"fehlt entschuldigt"
									 			,"nicht gesetzt"=>"gebucht, noch nicht abgeschlossen")
									 , array()
									 , ""
									 , 220
									 , 160
									 , "text"
									 , "asc"
									 , true
									 )
						->multiselect("booking_status"
									 , $this->lng->txt("gev_booking_status")
									 , "booking_status"
									 , catFilter::getDistinctValues('booking_status', 'hist_usercoursestatus')
									 , array()
									 , ""
									 , 200
									 , 160	
									 )
						->multiselect("gender"
									 , $this->lng->txt("gender")
									 , "gender"
									 , array('f', 'm')
									 , array()
									 , ""
									 , 100
									 , 160	
									 )
						->multiselect("venue"
									 , $this->lng->txt("gev_venue")
									 , "venue"
									 , catFilter::getDistinctValues('venue', 'hist_course')
									 , array()
									 , ""
									 , 300
									 , 160	
									 )
						->multiselect("provider"
									 , $this->lng->txt("gev_provider")
									 , "provider"
									 , catFilter::getDistinctValues('provider', 'hist_course')
									 , array()
									 , ""
									 , 300
									 , 160	
									 )
						->static_condition($this->db->in("usr.user_id", $this->allowed_user_ids, false, "integer"))
						->static_condition("usr.hist_historic = 0")
						->static_condition("orgu.hist_historic = 0")
						->static_condition("orgu.action >= 0")
						->static_condition("usrcrs.booking_status != ".$this->db->quote('-empty-','text'))
						->action($this->ctrl->getLinkTarget($this, "view"))
						->compile();
		$this->relevant_parameters = array(
			$this->filter->getGETName() => $this->filter->encodeSearchParamsForGET()
			); 
		$this->dates = $this->filter->get("period");
        foreach($this->dates as &$il_date_obj) {
            $il_date_obj = $il_date_obj->get(IL_CAL_DATE);
        }



	//Saving this fields outside the filter will enable us to cout the right employee-numbers,
	//including the ones, that did not participate in a training or did so outside the period defined above	

		$this->sql_sum_parts = array(

				"sum_booked" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.booking_status) = 'gebucht'
							AND LCASE(usrcrs.participation_status) = 'nicht gesetzt'
							AND crs.type != 'Selbstlernkurs'
						THEN 1
						END 
					) AS sum_booked",

					"sum_booked_wbt" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.booking_status) = 'gebucht'
							AND LCASE(usrcrs.participation_status) = 'nicht gesetzt'
							AND crs.type = 'Selbstlernkurs'
						THEN 1
						END 
					) AS sum_booked_wbt",


					"sum_waiting" => "SUM(
						CASE 
							WHEN usrcrs.booking_status = 'auf Warteliste'
							AND participation_status = 'nicht gesetzt'
						THEN 1
						END 
					) AS sum_waiting",

					"sum_attended" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.participation_status) = 'teilgenommen'
							AND crs.type != 'Selbstlernkurs'
						THEN 1
						END 
					) AS sum_attended",

					"sum_attended_wbt" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.participation_status) = 'teilgenommen'
							AND crs.type = 'Selbstlernkurs'
						THEN 1
						END 
					) AS sum_attended_wbt",


					"sum_excused" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.participation_status) = 'fehlt entschuldigt'
						THEN 1
						END 
					) AS sum_excused",


					"sum_unexcused" => " SUM(
						CASE 
							WHEN LCASE(usrcrs.participation_status) = 'fehlt ohne Absage'
						THEN 1
						END 
					) AS sum_unexcused",

					"sum_exit" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.participation_status) = 'canceled_exit'
						THEN 1
						END 
					) AS sum_exit"

			);

		$this->query = catReportQuery::create()
						//->distinct()

						->select("orgu.orgu_title")
						->select("orgu.org_unit_above1")
						->select("orgu.org_unit_above2")
						/*->select("usr.gender")

						->select("crs.venue")
						->select("crs.provider")

						->select("usrcrs.booking_status")
						->select("usrcrs.participation_status")
						->select("usr.user_id")
						->select("crs.crs_id")
						*/
						->select_raw("COUNT(DISTINCT orgu.usr_id) as sum_employees")
						->select_raw($this->sql_sum_parts['sum_booked_wbt'])
						->select_raw($this->sql_sum_parts['sum_attended_wbt'])

						->select_raw($this->sql_sum_parts['sum_booked'])
						->select_raw($this->sql_sum_parts['sum_attended'])
						->select_raw($this->sql_sum_parts['sum_waiting'])
						->select_raw($this->sql_sum_parts['sum_excused'])
						->select_raw($this->sql_sum_parts['sum_unexcused'])
						->select_raw($this->sql_sum_parts['sum_exit']);
		$orgu_filter->addToQuery($this->query);
		$this->query	->from("hist_user usr")
						->left_join("hist_usercoursestatus usrcrs")
							->on("usrcrs.usr_id = usr.user_id AND usrcrs.hist_historic = 0 "
							."	AND ((`usrcrs`.`end_date` >= ".$this->db->quote($this->dates["start"],"date")
							." 		OR `usrcrs`.`end_date` = ".$this->db->quote("0000-00-00","date")
							." 		OR `usrcrs`.`end_date` = ".$this->db->quote("-empty-","text").")"
							."  	AND `usrcrs`.`begin_date` <= ".$this->db->quote($this->dates["end"],"date").")")
						->left_join("hist_course crs")
							->on("usrcrs.crs_id = crs.crs_id AND crs.hist_historic = 0")
						->join("hist_userorgu orgu")
							->on("usr.user_id = orgu.usr_id")
						->group_by("orgu.orgu_id")
						->compile();
	}



	protected function _process_xls_date($val) {
		$val = str_replace('<nobr>', '', $val);
		$val = str_replace('</nobr>', '', $val);
		return $val;
	}


	protected function transformResultRow($rec) {
		$rec['odbd'] = $rec['org_unit_above2'] .'/' .$rec['org_unit_above1'];
		return $this->replaceEmpty($rec);
	}




	protected function renderView() {
		$main_table = $this->renderTable();
		return 	$this->renderSumTable()
				.$main_table;
	}


	private function renderSumTable(){

		$table = new catTableGUI($this, "view");
		$table->setEnableTitle(false);
		$table->setTopCommands(false);
		$table->setEnableHeader(true);
		$table->setRowTemplate(
			$this->table_sums->row_template_filename, 
			$this->table_sums->row_template_module
		);

		$table->addColumn("", "blank", "0px", false);
		foreach ($this->table_sums->columns as $col) {
			$table->addColumn( $col[2] ? $col[1] : $this->lng->txt($col[1])
							 , $col[0]
							 , $col[3]
							 );
		}		

		$sum_sql = 
		"SELECT COUNT(DISTINCT user_id) as sum_employees, "
			."SUM(CASE WHEN LCASE(booking_status)='gebucht' AND LCASE(participation_status) = 'nicht gesetzt' AND type = 'Selbstlernkurs' THEN 1 END ) AS sum_booked_wbt,"
			."SUM(CASE WHEN LCASE(participation_status)='teilgenommen' AND type = 'Selbstlernkurs' THEN 1 END ) AS sum_attended_wbt,"
			."SUM(CASE WHEN LCASE(booking_status)='gebucht' AND LCASE(participation_status) = 'nicht gesetzt' AND type != 'Selbstlernkurs' THEN 1 END ) AS sum_booked,"
			."SUM(CASE WHEN LCASE(participation_status) = 'teilgenommen' AND type != 'Selbstlernkurs' THEN 1 END ) AS sum_attended,"
			."SUM(CASE WHEN booking_status = 'auf Warteliste' AND participation_status = 'nicht gesetzt' THEN 1 END ) AS sum_waiting,"
			."SUM(CASE WHEN LCASE(participation_status) = 'fehlt entschuldigt' THEN 1 END ) AS sum_excused,"
			."SUM(CASE WHEN LCASE(participation_status) = 'fehlt ohne Absage' THEN 1 END ) AS sum_unexcused,"
			."SUM(CASE WHEN LCASE(participation_status) = 'canceled_exit' THEN 1 END ) AS sum_exit "
			."FROM("
			."	SELECT DISTINCT usr.user_id, crs.crs_id, usrcrs.booking_status, "
			."		usrcrs.participation_status, crs.type "
			."		FROM `hist_user` usr "
			."		JOIN hist_userorgu orgu ON usr.user_id = orgu.usr_id"
			."		LEFT JOIN `hist_usercoursestatus` usrcrs "
			."			ON usrcrs.usr_id = usr.user_id AND usrcrs.hist_historic = 0 "
			."			AND ((`usrcrs`.`end_date` >= ".$this->db->quote($this->dates["start"],"date")
			."	 			OR `usrcrs`.`end_date` = ".$this->db->quote("0000-00-00","date")
			."	 			OR `usrcrs`.`end_date` = ".$this->db->quote("-empty-","text").")"
			."	 			AND `usrcrs`.`begin_date` <= ".$this->db->quote($this->dates["end"],"date").")"
			."		LEFT JOIN `hist_course` crs ON usrcrs.crs_id = crs.crs_id AND crs.hist_historic = 0 "
			."		".$this->queryWhere()
			.") as temp";
		$res = $this->db->query($sum_sql);
		$this->summed_data = $this->db->fetchAssoc($res);
		$cnt = 1;
		$table->setLimit($cnt);
		$table->setMaxCount($cnt);

		if(count($this->summed_data) == 0) {
			foreach(array_keys($this->table_sums->columns) as $field) {
				$this->summed_data[$field] = 0;
			}
		}

		$table->setData(array($this->summed_data));
		$this->enableRelevantParametersCtrl();
		$return = $table->getHtml();
		$this->disableRelevantParametersCtrl();
		return $return;
	}

	protected function checkPermissionOnTarget() {
		if (  $this->user_utils->isAdmin() ||  $this->user_utils->hasRoleIn(array("Admin-Ansicht"))) {
			return;
		}
		throw new Exception("No permission to view report for user $a_target_user_id");
	}


}