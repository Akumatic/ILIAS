<?php

require_once 'Services/ReportsRepository/classes/class.ilObjReportBaseGUI.php';
require_once 'Services/Form/classes/class.ilCheckboxInputGUI.php';
require_once 'Services/Form/classes/class.ilSelectInputGUI.php';
/**
* User Interface class for example repository object.
* ...
* @ilCtrl_isCalledBy ilObjReportBillGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
* @ilCtrl_Calls ilObjReportBillGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI,
* @ilCtrl_Calls ilObjReportBillGUI: ilCommonActionDispatcherGUI
*/
class ilObjReportBillGUI extends ilObjReportBaseGUI {

	static protected $bill_link_icon;

	protected function afterConstructor() {
		parent::afterConstructor();
		self::$bill_link_icon = '<img src="'.ilUtil::getImagePath("GEV_img/ico-key-get_bill.png").'" />';
	}

	public function getType() {
		return 'xrbi';
	}


	public function performCommand() {
		$cmd = $this->gCtrl->getCmd();
			        
		switch ($cmd) {
			case "deliverBillPDF":
				if($this->gAccess->checkAccess("read", "", $this->object->getRefId())) {
					return $this->deliverBillPDF();
				}
				break;
			default:
				parent::performCommand();
		}
	}

	protected function settingsForm($data = null) {
		$settings_form = parent::settingsForm($data);

		$report_mode = new ilSelectInputGUI($this->gLng->txt('gev_report_mode'),'report_mode');
		$options = array();

		foreach(ilObjReportBill::$config as $key => $settings) {
			$options[$key] = $settings["label"];
		}
		$report_mode->setOptions($options);
		if(isset($data["report_mode"])) {
			$report_mode->setValue($data["report_mode"]);
		}
		$settings_form->addItem($report_mode);

		return $settings_form;
	}

	protected function prepareTitle($a_title) {
		$a_title = parent::prepareTitle($a_title);
		$a_title->image("GEV_img/ico-head-rep-billing.png");
		return $a_title;
	}

	protected function getSettingsData() {
		$data = parent::getSettingsData();
		$data["report_mode"] = $this->object->getReportMode();
		return $data;
	}

	protected function saveSettingsData($data) {
		$this->object->setReportMode($data["report_mode"]);
		parent::saveSettingsData($data);
	}

	public static function transformResultRow($rec) {
		global $ilCtrl;
		foreach ($rec as $key => $value) {
				
			if ($value == '-empty-' || $value == -1) {
				$rec[$key] = "";
				continue;
			}
			if($rec["is_vfs"] == 0) {
				$rec["assigment"] = "GEV";
			} else {
				$rec["assigment"] = "VFS";
			}

			//date
			if( $rec["begin_date"] && $rec["end_date"] 
				&& ($rec["begin_date"] != '0000-00-00' && $rec["end_date"] != '0000-00-00' )
				){
				$start = new ilDate($rec["begin_date"], IL_CAL_DATE);
				$end = new ilDate($rec["end_date"], IL_CAL_DATE);
				$date = '<nobr>' .ilDatePresentation::formatPeriod($start,$end) .'</nobr>';
				//$date = ilDatePresentation::formatPeriod($start,$end);
			} else {
				$date = '-';
			}
			$rec['date'] = $date;
		}

		$rec["fee_pretax"] = number_format($rec["fee_pretax"], 2, ',', '.');
		$rec["fee_tax"] = number_format($rec["fee_tax"], 2, ',', '.');
		$rec["fee_posttax"] = number_format($rec["fee_posttax"], 2, ',', '.');
		$rec["coupon_pretax"] = number_format($rec["coupon_pretax"], 2, ',', '.');
		$rec["coupon_tax"] = number_format($rec["coupon_tax"], 2, ',', '.');
		$rec["coupon_posttax"] = number_format($rec["coupon_posttax"], 2, ',', '.');
		$rec["amount_pretax"] = number_format($rec["amount_pretax"], 2, ',', '.');
		$rec["amount_tax"] = number_format($rec["amount_tax"], 2, ',', '.');
		$rec["amount_posttax"] = number_format($rec["amount_posttax"], 2, ',', '.');


		$ilCtrl->setParameterByClass("ilObjReportBillGUI", "billnumber", $rec["billnumber"]);
		$target = $ilCtrl->getLinkTargetByClass("ilObjReportBillGUI", "deliverBillPDF");
		//$this->ctrl->clearParameters();
		$ilCtrl->setParameterByClass("ilObjReportBillGUI", "billnumber", null);
		$rec["bill_link"] = "<a href=\"".$target."\">".self::$bill_link_icon."</a>";
			
		return $rec;
	}

	protected function deliverBillPDF() {
		$billnumber = $_GET["billnumber"];
		if (!preg_match("/\d{6}-\d{5}/", $billnumber)) {
			throw Exception("gevBillingReportGUI::deliverBillPDF: This is no billnumber: '".$billnumber."'");
		}
		require_once("Services/Utilities/classes/class.ilUtil.php");
		require_once("Services/GEV/Utils/classes/class.gevBillStorage.php");
		$filename = gevBillStorage::getInstance()->getPathByBillNumber($billnumber);
		ilUtil::deliverFile($filename, $billnumber.".pdf", "application/pdf");
	}
}