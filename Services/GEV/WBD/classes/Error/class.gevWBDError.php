<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */#

/**
* abstract class for the WBD-Error
* must be implemented for each WBD-Error
*
* @author	Stefan Hecken <shecken@concepts-and-training.de>
* @version	$Id$
*
*/
class gevWBDError extends WBDError{

	const ERROR_GROUP_CRS = "crs";
	const ERROR_GROUP_USER = "user";

	protected $ilDB;
	protected $errMessage;
	protected $reason;
	protected $internal;
	protected $service;
	protected $usr_id;
	protected $crs_id;
	protected $row_id;
	protected $error_group;

	protected static $valid_services 
		= array(
			'new_user'
			,'update_user'
			,'release_user'
			,'affiliate_user'
			,'cp_report'
			,'cp_storno'
			,'cp_request'
			);

	public function __construct($errMessage, $error_group ,$service, $usr_id, $row_id, $crs_id = 0) {

		global $ilDB;

		$this->ilDB = $ilDB;

		$this->usr_id = $usr_id;
		$this->row_id = $row_id;
		$this->crs_id = $crs_id;
		$this->service = $service;
		$this->errMessage = $errMessage;
		$this->error_group = $error_group;
		$this->findReason();

		if($this->usr_id === null) {
			throw new LogicException("gevWBDError::userId:user_id is null");
		}

		if($this->row_id === null) {
			throw new LogicException("gevWBDError::rowId:row_id is null");
		}
		if(!in_array(strtolower($this->service), self::$valid_services)) {
			throw new LogicException("gevWBDError::service:service invalid");
		}
	}
	/**
	*transaltes errMessage to an internal reason string
	*/
	protected function findReason() {

		if($this->ilDB === null) {
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			die();
		}
		$sql = "SELECT reason_string, internal\n"
				." FROM wbd_errors_categories\n"
				." WHERE error_group = ".$this->ilDB->quote($this->error_group,"text")."\n"
				." AND LOCATE( failure,".$this->ilDB->quote($this->errMessage,"text")." ) > 0";

		$res = $this->ilDB->fetchAssoc($this->ilDB->query($sql));
		$this->reason = $res["reason_string"] ? $res["reason_string"] : '-unknown-';
		$this->internal = $res["internal"] ? $res["internal"] : 0;
	}


	/**
	* Get the internal user id of the WBD Error
	*
	* @throws LogicException
	*
	* @return string $errMessage
	*/
	final public function userId() {
		return $this->usr_id;
	}

	/**
	* Get the inernal row id of the WBD Error
	*
	* @throws LogicException
	*
	* @return string $errMessage
	*/
	final public function rowId() {
		return $this->row_id;
	}

	/**
	* Get the internal crs id of the WBD Error
	*
	* @throws LogicException
	*
	* @return string $errMessage
	*/
	final public function crsId() {
		return $this->crs_id;
	}

	/**
	* Returns the distilled error reason to be stored in wbd_errors
	*
	*@return string $reason;
	*/
	final public function reason() {
		return $this->reason;
	}
	/**
	* Returns internal status to be stored in wbd_errors
	*
	*@return bool;
	*/
	final public function internal() {
		return $this->internal;
	}
	/**
	* Returns  service type to be stored in wbd_errors
	*
	*@return string $service;
	*/
	final public function service() {
		return $this->service;
	}
}