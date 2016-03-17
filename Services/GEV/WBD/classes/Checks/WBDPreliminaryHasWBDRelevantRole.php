<?php
require_once ("Services/GEV/WBD/classes/Interfaces/WBDPreliminary.php");

class WBDPreliminaryHasWBDRelevantRole extends WBDPreliminary {
	static $message = "gev_wbd_check_no_wbdrelevant_role";

	public function message() {
		return self::$message;
	}

	/** 
	 * @inheritdoc 
	 */
	public function performCheck(gevWBD $wbd) {
		return $wbd->hasWBDRelevantRole();
	}
}

