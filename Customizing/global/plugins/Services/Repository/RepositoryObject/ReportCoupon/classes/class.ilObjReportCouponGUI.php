<?php

require_once 'Services/ReportsRepository/classes/class.ilObjReportBaseGUI.php';
require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
require_once 'Services/Form/classes/class.ilCheckboxInputGUI.php';
/**
* User Interface class for example repository object.
* ...
* @ilCtrl_isCalledBy ilObjReportCouponGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
* @ilCtrl_Calls ilObjReportCouponGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI
* @ilCtrl_Calls ilObjReportCouponGUI: ilCommonActionDispatcherGUI
*/
class ilObjReportCouponGUI extends ilObjReportBaseGUI {

	static $od_bd_regexp = null;

	public function getType() {
		return 'xrcp';
	}

	protected function settingsForm($data = null) {
		$settings_form = parent::settingsForm($data);

		$admin_mode = new ilCheckboxInputGUI($this->gLng->txt('gev_coupon_report_admin_mode'),'admin_mode');
		if(isset($data["admin_mode"])) {
			$admin_mode->setChecked($data["admin_mode"]);
		}
		$settings_form->addItem($admin_mode);

		return $settings_form;
	}

	protected function prepareTitle($a_title) {
		$a_title = parent::prepareTitle($a_title);
		$a_title->image("GEV_img/ico-head-rep-billing.png");
		return $a_title;
	}

	protected function getSettingsData() {
		$data = parent::getSettingsData();
		$data["admin_mode"] = $this->object->getAdminMode();
		return $data;
	}


	protected function saveSettingsData($data) {
		$this->object->setAdminMode($data["admin_mode"]);
		parent::saveSettingsData($data);
	}

	public static function transformResultRow($a_rec) {

		if(!self::$od_bd_regexp) {
			require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/ReportCoupon/config/od_bd_strings.php';
		}
		$orgus_above1 = explode(';', $a_rec["above1"]);
		$orgus_above2 = explode(';', $a_rec["above2"]);
		$orgus = array();
		foreach (array_unique(array_merge($orgus_above1, $orgus_above2)) as $value) {
			if (preg_match(self::$od_bd_regexp, $value)) {
				$orgus[] = $value;
			}
		}
		$a_rec["odbd"]	=  implode(', ', array_unique($orgus));
		$a_rec["current"] = number_format($a_rec["current"], 2, ',', '.');
		$a_rec["start"] = number_format($a_rec["start"], 2, ',', '.');
		$a_rec["diff"] = number_format($a_rec["diff"], 2, ',', '.');
		$a_rec = parent::transformResultRow($a_rec);
		return $a_rec;
	}

	public static function transformResultRowXLS($a_rec) {
		$a_rec = static::transformResultRow($a_rec);
		return $a_rec;
	}
}