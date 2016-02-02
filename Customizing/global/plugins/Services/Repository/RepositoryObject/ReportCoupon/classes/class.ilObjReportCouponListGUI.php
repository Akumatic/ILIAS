<?php
 
require_once 'Services/ReportsRepository/classes/class.ilObjReportBaseListGUI.php';
/**
* ListGUI implementation for Example object plugin. This one
* handles the presentation in container items (categories, courses, ...)
* together with the corresponfing ...Access class.
*
* PLEASE do not create instances of larger classes here. Use the
* ...Access class to get DB data and keep it small.
*/
class ilObjReportCouponListGUI extends ilObjReportBaseListGUI {

/**
* Init type
*/
	public function initType() {
		$this->setType("xrcp");
		parent::initType();
	}

	/**
	* Get name of gui class handling the commands
	*/
	public function getGuiClass() {
		return "ilObjReportCouponGUI";
	}

	public function getProperties() {
		$props = array();

		$this->plugin->includeClass("class.ilObjReportCouponAccess.php");
		if (!ilObjReportCouponAccess::checkOnline($this->obj_id)) {
			$props[] = array("alert" => true, "property" => $this->lng->txt("status"),
			"value" => $this->lng->txt("offline"));
		}
		 
		return $props;
	}
}