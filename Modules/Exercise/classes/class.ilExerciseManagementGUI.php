<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Modules/Exercise/classes/class.ilExSubmission.php";
include_once "Modules/Exercise/classes/class.ilExSubmissionBaseGUI.php";

/**
* Class ilExerciseManagementGUI
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* 
* @ilCtrl_Calls ilExerciseManagementGUI: ilFileSystemGUI, ilRepositorySearchGUI
* @ilCtrl_Calls ilExerciseManagementGUI: ilExSubmissionGUI
* 
* @ingroup ModulesExercise
*/
class ilExerciseManagementGUI
{
	protected $exercise; // [ilObjExercise]
	protected $assignment; // [ilExAssignment]
	
	const VIEW_ASSIGNMENT = 1;
	const VIEW_PARTICIPANT = 1;	
	const VIEW_GRADES = 3;
	
	/**
	 * Constructor
	 * 
	 * @param int $a_exercise_id
	 * @return object
	 */
	public function __construct(ilObjExercise $a_exercise, ilExAssignment $a_ass = null)
	{		
		global $ilCtrl, $ilTabs, $lng, $tpl;
		
		$this->exercise = $a_exercise;
		$this->assignment = $a_ass;
		
		// :TODO:
		$this->ctrl = $ilCtrl;
		$this->tabs_gui = $ilTabs;
		$this->lng = $lng;
		$this->tpl = $tpl;				
	}
	
	public function executeCommand()
	{
		global $ilCtrl, $lng, $ilTabs;
		
		$this->ctrl->saveParameter($this, array("fsmode"));
		
		$class = $ilCtrl->getNextClass($this);
		$cmd = $ilCtrl->getCmd("listPublicSubmissions");		
		
		switch($class)
		{			
			case "ilfilesystemgui":							
				$ilCtrl->saveParameter($this, array("member_id"));
				$ilTabs->clearTargets();

				if ($_GET["fsmode"] != "feedbackpart")
				{
					$ilTabs->setBackTarget($lng->txt("back"),
						$ilCtrl->getLinkTarget($this, "members"));
				}
				else
				{
					$ilTabs->setBackTarget($lng->txt("back"),
						$ilCtrl->getLinkTarget($this, "showParticipant"));
				}

				ilUtil::sendInfo($lng->txt("exc_fb_tutor_info"));

				include_once("./Modules/Exercise/classes/class.ilFSStorageExercise.php");
				$fstorage = new ilFSStorageExercise($this->exercise->getId(), (int) $_GET["ass_id"]);
				$fstorage->create();

				include_once("./Services/User/classes/class.ilUserUtil.php");
				$noti_rec_ids = array();
				if($this->ass->getType() == ilExAssignment::TYPE_UPLOAD_TEAM)
				{
					$team_id = $this->ass->getTeamId((int) $_GET["member_id"]);
					$feedback_id = "t".$team_id;
					$fs_title = array();
					foreach($this->ass->getTeamMembers($team_id) as $team_user_id)
					{
						$fs_title[] = ilUserUtil::getNamePresentation($team_user_id, false, false, "", true);
						$noti_rec_ids[] = $team_user_id;
					}
					$fs_title = implode(" / ", $fs_title);
				}
				else
				{
					$feedback_id = $noti_rec_ids = (int) $_GET["member_id"];
					$fs_title = ilUserUtil::getNamePresentation((int) $_GET["member_id"], false, false, "", true);
				}

				include_once("./Services/FileSystem/classes/class.ilFileSystemGUI.php");
				$fs_gui = new ilFileSystemGUI($fstorage->getFeedbackPath($feedback_id));
				$fs_gui->setTableId("excfbfil".(int)$_GET["ass_id"]."_".$feedback_id);
				$fs_gui->setAllowDirectories(false);					
				$fs_gui->setTitle($lng->txt("exc_fb_files")." - ".
					ilExAssignment::lookupTitle((int) $_GET["ass_id"])." - ".
					$fs_title);
				$pcommand = $fs_gui->getLastPerformedCommand();					
				if (is_array($pcommand) && $pcommand["cmd"] == "create_file")
				{
					$this->exercise->sendFeedbackFileNotification($pcommand["name"], 
						$noti_rec_ids, (int) $_GET["ass_id"]);
				}					 
				$this->ctrl->forwardCommand($fs_gui);
				break;
				
			case 'ilrepositorysearchgui':
				include_once('./Services/Search/classes/class.ilRepositorySearchGUI.php');
				$rep_search = new ilRepositorySearchGUI();			
				$rep_search->setTitle($this->lng->txt("exc_add_participant"));
				$rep_search->setCallback($this,'addMembersObject');

				// Set tabs
				$this->addSubTabs("assignment");
				$this->ctrl->setReturn($this,'members');
				
				$this->ctrl->forwardCommand($rep_search);
				break;
			
			case "ilexsubmissionteamgui":										
				include_once "Modules/Exercise/classes/class.ilExSubmissionTeamGUI.php";
				$gui = new ilExSubmissionTeamGUI($this->exercise, $this->initSubmission());
				$ilCtrl->forwardCommand($gui);				
				break;		
				
			case "ilexsubmissionfilegui":													
				include_once "Modules/Exercise/classes/class.ilExSubmissionFileGUI.php";
				$gui = new ilExSubmissionFileGUI($this->exercise, $this->initSubmission());
				$ilCtrl->forwardCommand($gui);				
				break;
				
			case "ilexsubmissiontextgui":															
				include_once "Modules/Exercise/classes/class.ilExSubmissionTextGUI.php";
				$gui = new ilExSubmissionTextGUI($this->exercise, $this->initSubmission());
				$ilCtrl->forwardCommand($gui);				
				break;
			
			default:					
				$this->ctrl->setParameter($this, "fsmode", ""); // #15115
				
				$this->{$cmd."Object"}();				
				break;
		}
	}	
	
	protected function initSubmission()
	{
		if($_GET["lmem"] || $_GET["lpart"])
		{
			if($_GET["lmem"])
			{
				$this->ctrl->saveParameter($this, "lmem");
				$user_id = $_GET["lmem"];
				$back_cmd = "members";
			}
			else
			{
				$this->ctrl->saveParameter($this, "lpart");
				$user_id = $_GET["lpart"];
				$back_cmd = "showParticipant";
			}
			$this->ctrl->setReturn($this, $back_cmd);
			
			$this->tabs_gui->clearTargets();		
			$this->tabs_gui->setBackTarget($this->lng->txt("back"), 
				$this->ctrl->getLinkTarget($this, $back_cmd));	

			include_once "Modules/Exercise/classes/class.ilExSubmission.php";
			return new ilExSubmission($this->assignment, $user_id, null, true);
		}
	}
	
	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function addSubTabs($a_activate)
	{
		global $ilTabs, $lng, $ilCtrl;
		
		$ilTabs->addSubTab("assignment", $lng->txt("exc_assignment_view"),
			$ilCtrl->getLinkTarget($this, "members"));
		$ilTabs->addSubTab("participant", $lng->txt("exc_participant_view"),
			$ilCtrl->getLinkTarget($this, "showParticipant"));
		$ilTabs->addSubTab("grades", $lng->txt("exc_grades_overview"),
			$ilCtrl->getLinkTarget($this, "showGradesOverview"));
		$ilTabs->activateSubTab($a_activate);
	}
	
	/**
	 * All participants and submission of one assignment
	 */
	function membersObject()
	{
		global $tpl, $ilToolbar, $ilCtrl, $lng;

		include_once 'Services/Tracking/classes/class.ilLPMarks.php';
	
		$this->addSubTabs("assignment");
		
		// assignment selection
		include_once("./Modules/Exercise/classes/class.ilExAssignment.php");
		$ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());
		
		if (!$this->assignment)
		{
			$this->assignment = current($ass);			
		}
		
		reset($ass);
		if (count($ass) > 1)
		{
			$options = array();
			foreach ($ass as $a)
			{
				$options[$a->getId()] = $a->getTitle();
			}
			include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
			$si = new ilSelectInputGUI($this->lng->txt(""), "ass_id");
			$si->setOptions($options);
			$si->setValue($this->assignment->getId());
			$ilToolbar->addInputItem($si);
					
			$ilToolbar->addFormButton($this->lng->txt("exc_select_ass"),
				"selectAssignment");
			$ilToolbar->addSeparator();
		}
		
		// add member
		include_once './Services/Search/classes/class.ilRepositorySearchGUI.php';
		ilRepositorySearchGUI::fillAutoCompleteToolbar(
			$this,
			$ilToolbar,
			array(
				'auto_complete_name'	=> $lng->txt('user'),
				'submit_name'			=> $lng->txt('add'),
				'add_search'			=> true,
				'add_from_container'    => $this->exercise->getRefId()
			)
		);
		
		// we do not want the ilRepositorySearchGUI form action
		$ilToolbar->setFormAction($ilCtrl->getFormAction($this));

		$ilToolbar->addSeparator();
		
		// multi-feedback
		$ilToolbar->addButton($this->lng->txt("exc_multi_feedback"),
			$this->ctrl->getLinkTarget($this, "showMultiFeedback"));
		
		if (count($ass) > 0)
		{							
			if($this->assignment->getType() == ilExAssignment::TYPE_TEXT)
			{
				$ilToolbar->addSeparator();
				$ilToolbar->addFormButton($lng->txt("exc_list_text_assignment"), "listTextAssignment");					
			}		
			else if(ilExSubmission::hasAnySubmissions($this->assignment->getId()))
			{			
				$ilToolbar->addSeparator();
				$ilToolbar->addFormButton($lng->txt("download_all_returned_files"), "downloadAll");			
			}		
			
			include_once("./Modules/Exercise/classes/class.ilExerciseMemberTableGUI.php");
			$exc_tab = new ilExerciseMemberTableGUI($this, "members", $this->exercise, $this->assignment);
			$tpl->setContent($exc_tab->getHTML());
		}
		else
		{
			ilUtil::sendInfo($lng->txt("exc_no_assignments_available"));
		}
		return;		
	}
	
	

	
	/**
	 * Save grades
	 */
	function saveGradesObject()
	{
		global $ilCtrl, $lng;
				
		include_once 'Services/Tracking/classes/class.ilLPMarks.php';
		
		if (is_array($_POST["lcomment"]))
		{
			foreach ($_POST["lcomment"] as $k => $v)
			{
				$marks_obj = new ilLPMarks($this->exercise->getId(), (int) $k);
				$marks_obj->setComment(ilUtil::stripSlashes($v));
				$marks_obj->setMark(ilUtil::stripSlashes($_POST["mark"][$k]));
				$marks_obj->update();
			}
		}
		ilUtil::sendSuccess($lng->txt("exc_msg_saved_grades"), true);
		$ilCtrl->redirect($this, "showGradesOverview");
	}
	
	
	// TEXT ASSIGNMENT ?!
	
	function listTextAssignmentWithPeerReviewObject()
	{
		$this->listTextAssignmentObject(true);
	}
	
	function listTextAssignmentObject($a_show_peer_review = false)
	{
		global $tpl, $ilToolbar, $ilCtrl, $ilTabs, $lng;
				
		if(!$this->assignment || $this->assignment->getType() != ilExAssignment::TYPE_TEXT)
		{
			$ilCtrl->redirect($this, "members");
		}

		$ilTabs->clearTargets();
		$ilTabs->setBackTarget($lng->txt("back"),
			$ilCtrl->getLinkTarget($this, "members"));
		
		if($a_show_peer_review)
		{
			$cmd = "listTextAssignmentWithPeerReview";
		}
		else
		{
			$cmd = "listTextAssignment";
		}
		include_once "Modules/Exercise/classes/class.ilExAssignmentListTextTableGUI.php";
		$tbl = new ilExAssignmentListTextTableGUI($this, $cmd, $this->assignment, $a_show_peer_review);		
		$tpl->setContent($tbl->getHTML());		
	}
	
	
	
	/**
	* Add user as member
	*/
	public function addUserFromAutoCompleteObject()
	{		
		if(!strlen(trim($_POST['user_login'])))
		{
			ilUtil::sendFailure($this->lng->txt('msg_no_search_string'));
			$this->membersObject();
			return false;
		}
		$users = explode(',', $_POST['user_login']);

		$user_ids = array();
		foreach($users as $user)
		{
			$user_id = ilObjUser::_lookupId($user);

			if(!$user_id)
			{
				ilUtil::sendFailure($this->lng->txt('user_not_known'));
				return $this->membersObject();		
			}
			
			$user_ids[] = $user_id;
		}

		if(!$this->addMembersObject($user_ids));
		{
			$this->membersObject();
			return false;
		}
		return true;
	}

	/**
	 * Add new partipant
	 */
	function addMembersObject($a_user_ids = array())
	{
		global $ilAccess,$ilErr;

		if(!count($a_user_ids))
		{
			ilUtil::sendFailure($this->lng->txt("no_checkbox"));
			return false;
		}

		if(!$this->exercise->members_obj->assignMembers($a_user_ids))
		{
			ilUtil::sendFailure($this->lng->txt("exc_members_already_assigned"));
			return false;
		}
		else
		{
			// #9946 - create team for new user(s) for each team upload assignment
			foreach(ilExAssignment::getAssignmentDataOfExercise($this->exercise->getId()) as $ass)
			{
				if($ass["type"] == ilExAssignment::TYPE_UPLOAD_TEAM)
				{
					$ass_obj = new ilExAssignment($ass["id"]);
					foreach($a_user_ids as $user_id)
					{
						$ass_obj->getTeamId($user_id, true);
					}
				}
			}						
			
			ilUtil::sendSuccess($this->lng->txt("exc_members_assigned"),true);
		}
//exit;
		$this->ctrl->redirect($this, "members");
		return true;
	}
	

	/**
	 * Select assignment
	 */
	function selectAssignmentObject()
	{
		global $ilTabs;

		$_GET["ass_id"] = ilUtil::stripSlashes($_POST["ass_id"]);
		$this->membersObject();
	}
	
	/**
	 * Show Participant
	 */
	function showParticipantObject()
	{
		global $rbacsystem, $tree, $tpl, $ilToolbar, $ilCtrl, $ilTabs, $lng;

		$this->addSubTabs("participant");
		
		// participant selection
		include_once("./Modules/Exercise/classes/class.ilExAssignment.php");
		$ass = ilExAssignment::getAssignmentDataOfExercise($this->exercise->getId());
		$members = $this->exercise->members_obj->getMembers();
		
		if (count($members) == 0)
		{
			ilUtil::sendInfo($lng->txt("exc_no_participants"));
			return;
		}
		
		$mems = array();
		foreach ($members as $mem_id)
		{
			if (ilObject::_lookupType($mem_id) == "usr")
			{
				include_once("./Services/User/classes/class.ilObjUser.php");
				$name = ilObjUser::_lookupName($mem_id);
				$mems[$mem_id] = $name;
			}
		}
		
		$mems = ilUtil::sortArray($mems, "lastname", "asc", false, true);
		
		if ($_GET["part_id"] == "" && count($mems) > 0)
		{
			$_GET["part_id"] = key($mems);
		}
		
		reset($mems);
		if (count($mems) > 1)
		{
			$options = array();
			foreach ($mems as $k => $m)
			{
				$options[$k] =
					$m["lastname"].", ".$m["firstname"]." [".$m["login"]."]";
			}
			include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
			$si = new ilSelectInputGUI($this->lng->txt(""), "part_id");
			$si->setOptions($options);
			$si->setValue($_GET["part_id"]);
			$ilToolbar->addInputItem($si);
			
			$ilToolbar->setFormAction($ilCtrl->getFormAction($this));
			$ilToolbar->addFormButton($this->lng->txt("exc_select_part"),
				"selectParticipant");
		}

		if (count($mems) > 0)
		{
			include_once("./Modules/Exercise/classes/class.ilExParticipantTableGUI.php");
			$part_tab = new ilExParticipantTableGUI($this, "showParticipant",
				$this->exercise, $_GET["part_id"]);
			$tpl->setContent($part_tab->getHTML());
		}
		else
		{
			ilUtil::sendInfo($this->lng->txt("exc_no_assignments_available"));
		}
	}
	
	/**
	 * Select participant
	 */
	function selectParticipantObject()
	{
		global $ilTabs;

		$_GET["part_id"] = ilUtil::stripSlashes($_POST["part_id"]);
		$this->showParticipantObject();
	}

	/**
	 * Show grades overview
	 */
	function showGradesOverviewObject()
	{
		global $rbacsystem, $tree, $tpl, $ilToolbar, $ilCtrl, $ilTabs, $lng;
		
		$this->addSubTabs("grades");
		
		include_once("./Modules/Exercise/classes/class.ilExAssignment.php");
		$mem_obj = new ilExerciseMembers($this->exercise);
		$mems = $mem_obj->getMembers();

		if (count($mems) > 0)
		{
			$ilToolbar->addButton($lng->txt("exc_export_excel"),
				$ilCtrl->getLinkTarget($this, "exportExcel"));
		}

		include_once("./Modules/Exercise/classes/class.ilExGradesTableGUI.php");
		$grades_tab = new ilExGradesTableGUI($this, "showGradesOverview",
			$this->exercise, $mem_obj);
		$tpl->setContent($grades_tab->getHTML()); 
	}

	/**
	* set feedback status for member and redirect to mail screen
	*/
	function redirectFeedbackMailObject()
	{		
		$members = array();
						
		if ($_GET["member_id"] != "")
		{	
			if($this->ass->getType() == ilExAssignment::TYPE_UPLOAD_TEAM)
			{
				$members = ilExAssignment::getTeamMembersByAssignmentId($this->ass->getId(), $_GET["member_id"]);
			}
			else
			{
				$members = array($_GET["member_id"]);
			}			
		}
		else if(count($_POST["member"]) > 0)
		{
			if($this->ass->getType() == ilExAssignment::TYPE_UPLOAD_TEAM)
			{
				foreach(array_keys($_POST["member"]) as $user_id)
				{
					$members = array_merge($members, ilExAssignment::getTeamMembersByAssignmentId($this->ass->getId(), $user_id));
				}
				$members = array_unique($members);
			}
			else
			{
				$members = array_keys($_POST["member"]);	
			}
		}
		
		if($members)
		{
			$logins = array();
			foreach($members as $user_id)
			{				
				$member_status = $this->ass->getMemberStatus($user_id);
				$member_status->setFeedback(true);
				$member_status->update();

				$logins[] = ilObjUser::_lookupLogin($user_id);
			}
			$logins = implode($logins, ",");
						
			require_once 'Services/Mail/classes/class.ilMailFormCall.php';
			ilUtil::redirect(ilMailFormCall::getRedirectTarget($this, 'members', array(), array('type' => 'new', 'rcp_to' => $logins)));
		}

		ilUtil::sendFailure($this->lng->txt("no_checkbox"),true);
		$this->ctrl->redirect($this, "members");
	}
	
	/**
	* Download all submitted files (of all members).
	*/
	function downloadAllObject()
	{		
		$members = array();
		
		foreach($this->exercise->members_obj->getMembers() as $member_id)
		{
			$submission = new ilExSubmission($this->assignment, $member_id);
			$submission->updateTutorDownloadTime();
			
			// get member object (ilObjUser)
			if (ilObject::_exists($member_id))
			{
				$tmp_obj =& ilObjectFactory::getInstanceByObjId($member_id);
				$members[$member_id] = $tmp_obj->getFirstname() . " " . $tmp_obj->getLastname();
				unset($tmp_obj);
			}
		}
	
		ilExSubmission::downloadAllAssignmentFiles($this->exercise->getId(),
			$this->assignment->getId(), $members);
		exit;
	}
	
	
	
/**
	* Send assignment per mail to participants
	*/
	function sendMembersObject()
	{
		global $ilCtrl;
		
		if(!count($_POST["member"]))
		{
			ilUtil::sendFailure($this->lng->txt("no_checkbox"),true);
		}
		else
		{			
			// team upload?
			if($this->ass->getType() == ilExAssignment::TYPE_UPLOAD_TEAM)
			{
				$members = array();
				foreach(array_keys($_POST["member"]) as $user_id)
				{					
					$tmembers = ilExAssignment::getTeamMembersByAssignmentId($this->ass->getId(), $user_id);
					foreach($tmembers as $tuser_id)
					{
						$members[$tuser_id] = 1;
					}
				}
			}
			else
			{
				$members = $_POST["member"];
			}
			
			$this->exercise->sendAssignment($this->assignment, $members);
			
			ilUtil::sendSuccess($this->lng->txt("exc_sent"),true);
		}
		$ilCtrl->redirect($this, "members");
	}

	/**
	* Confirm deassigning members
	*/
	function confirmDeassignMembersObject()
	{
		global $ilCtrl, $tpl, $lng, $ilTabs;
			
		if (!is_array($_POST["member"]) || count($_POST["member"]) == 0)
		{
			ilUtil::sendFailure($lng->txt("no_checkbox"), true);
			$ilCtrl->redirect($this, "members");
		}
		else
		{
			include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
			$cgui = new ilConfirmationGUI();
			$cgui->setFormAction($ilCtrl->getFormAction($this));
			$cgui->setHeaderText($lng->txt("exc_msg_sure_to_deassign_participant"));
			$cgui->setCancel($lng->txt("cancel"), "members");
			$cgui->setConfirm($lng->txt("remove"), "deassignMembers");
			
			// team upload?
			if($this->ass->getType() == ilExAssignment::TYPE_UPLOAD_TEAM)
			{
				$members = array();
				foreach(array_keys($_POST["member"]) as $user_id)
				{					
					$tmembers = ilExAssignment::getTeamMembersByAssignmentId($this->ass->getId(), $user_id);
					foreach($tmembers as $tuser_id)
					{
						$members[$tuser_id] = 1;
					}
				}
			}
			else
			{
				$members = $_POST["member"];
			}			
			
			include_once("./Services/User/classes/class.ilUserUtil.php");
			foreach ($members as $k => $m)
			{								
				$cgui->addItem("member[$k]", $m,
					ilUserUtil::getNamePresentation((int) $k, false, false, "", true));
			}
			
			$tpl->setContent($cgui->getHTML());
		}
	}
	
	/**
	 * Deassign members from exercise 
	 */
	function deassignMembersObject()
	{
		global $ilCtrl, $lng;
		
		if(is_array($_POST["member"]))
		{
			foreach(array_keys($_POST["member"]) as $usr_id)
			{
				$this->exercise->members_obj->deassignMember((int) $usr_id);
			}
			ilUtil::sendSuccess($lng->txt("exc_msg_participants_removed"), true);
			$ilCtrl->redirect($this, "members");
		}
  		else
		{
			ilUtil::sendFailure($this->lng->txt("no_checkbox"),true);
			$ilCtrl->redirect($this, "members");
		}
	}

	function saveCommentsObject() 
	{	
		if(!isset($_POST['comments_value']))
		{
			return;
		}
  
		$this->exercise->members_obj->setNoticeForMember($_GET["member_id"],
			ilUtil::stripSlashes($_POST["comments_value"]));
		ilUtil::sendSuccess($this->lng->txt("exc_members_comments_saved"));
		$this->membersObject();
	}


	/**
	 * Save assignment status (participant view)
	 */
	function saveStatusParticipantObject()
	{
		$this->saveStatusObject(true);
	}
	
	function saveStatusAllObject()
	{
		$this->saveStatusObject(false, true);
	}
	
	/**
	 * Save status of selecte members 
	 */
	function saveStatusObject($a_part_view = false, $a_force_all = false)
	{
		global $ilCtrl;
		
		include_once("./Modules/Exercise/classes/class.ilExAssignment.php");
		
//		include_once 'Services/Tracking/classes/class.ilLPMarks.php';

		$saved_for = array();
				
		foreach($_POST["id"] as $key => $value)
		{
			if (!$a_part_view)
			{						
				if (!$a_force_all && $_POST["member"][$key] != "1")
				{
					continue;
				}
				else
				{					
					$uname = ilObjUser::_lookupName($key);
					$saved_for[] = $uname["lastname"].", ".$uname["firstname"];					
				}
			}
			if (!$a_part_view)
			{
				$ass_id = (int) $_GET["ass_id"];
				$user_id = (int) $key;
			}
			else
			{
				$ass_id = (int) $key;
				$user_id = (int) $_GET["part_id"];
			}
			
			// team upload?
			if(is_object($this->ass) and $this->ass->getType() == ilExAssignment::TYPE_UPLOAD_TEAM)
			{
				$team_id = $this->ass->getTeamId($user_id);
				$user_ids = $this->ass->getTeamMembers($team_id);		
				
				if (count($_POST["member"]) > 0)
				{
					foreach($user_ids as $user_id)
					{
						if($user_id != $key)
						{
							$uname = ilObjUser::_lookupName($user_id);
							$saved_for[] = $uname["lastname"].", ".$uname["firstname"];
						}
					}
				}
			}
			else
			{
				$user_ids = array($user_id);
			}
			
			$ass = new ilExAssignment($ass_id);				
			foreach($user_ids as $user_id)
			{								
				$member_status = $ass->getMemberStatus($user_id);
				$member_status->setStatus(ilUtil::stripSlashes($_POST["status"][$key]));
				$member_status->setNotice(ilUtil::stripSlashes($_POST["notice"][$key]));			
				$member_status->setMark(ilUtil::stripSlashes($_POST["mark"][$key]));
				$member_status->update();
			}			
		}
		
		if (count($saved_for) > 0)
		{
			$save_for_str = "(".implode($saved_for, " - ").")";
		}
		if($save_for_str || $a_part_view)
		{
			ilUtil::sendSuccess($this->lng->txt("exc_status_saved")." ".$save_for_str,true);
		}		
		if (!$a_part_view)
		{
			$ilCtrl->redirect($this, "members");
		}
		else
		{
			$ilCtrl->redirect($this, "showParticipant");
		}
	}

	
	
	////
	//// Multi Feedback
	////
	
	function initMultiFeedbackForm($a_ass_id)
	{
		global $lng;
		
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->addCommandButton("uploadMultiFeedback", $lng->txt("upload"));
		$form->addCommandButton("members", $lng->txt("cancel"));
		
		// multi feedback file
		$fi = new ilFileInputGUI($lng->txt("exc_multi_feedback_file"), "mfzip");
		$fi->setSuffixes(array("zip"));
		$fi->setRequired(true);
		$form->addItem($fi);
				
		$form->setTitle(ilExAssignment::lookupTitle($a_ass_id));
		$form->setFormAction($this->ctrl->getFormAction($this, "uploadMultiFeedback"));		
		
		return $form;
	}
	
	/**
	 * Show multi-feedback screen
	 *
	 * @param
	 * @return
	 */
	function showMultiFeedbackObject(ilPropertyFormGUI $a_form = null)
	{
		global $ilTabs, $ilToolbar, $lng, $tpl;
		
		$ass_id = (int)$_GET["ass_id"];
		
		ilUtil::sendInfo($lng->txt("exc_multi_feedb_info"));
		
		$this->addSubTabs("assignment");
		
		// #13719
		include_once("./Services/UIComponent/Button/classes/class.ilLinkButton.php");
		$button = ilLinkButton::getInstance();				
		$button->setCaption("exc_download_zip_structure");
		$button->setUrl($this->ctrl->getLinkTarget($this, "downloadMultiFeedbackZip"));							
		$button->setOmitPreventDoubleSubmission(true);
		$ilToolbar->addButtonInstance($button);
		
		if(!$a_form)
		{
			$a_form = $this->initMultiFeedbackForm($ass_id);
		}
		
		$tpl->setContent($a_form->getHTML());
	}
	
	/**
	 * Download multi-feedback structrue file
	 */
	function downloadMultiFeedbackZipObject()
	{
		$ass = new ilExAssignment((int) $_GET["ass_id"]);
		$ass->sendMultiFeedbackStructureFile($this->exercise);
	}
	
	/**
	 * Upload multi feedback file
	 */
	function uploadMultiFeedbackObject()
	{		
		$ass_id = (int)$_GET["ass_id"];
		
		// #11983
		$form = $this->initMultiFeedbackForm($ass_id);
		if($form->checkInput())
		{
			try
			{
				$ass = new ilExAssignment($ass_id);
				$ass->uploadMultiFeedbackFile(ilUtil::stripSlashesArray($_FILES["mfzip"]));
				$this->ctrl->redirect($this, "showMultiFeedbackConfirmationTable");
			}
			catch (ilExerciseException $e)
			{
				ilUtil::sendFailure($e->getMessage(), true);
				$this->ctrl->redirect($this, "showMultiFeedback");
			}
		}
		
		$form->setValuesByPost();
		$this->showMultiFeedbackObject($form);
	}
	
	/**
	 * Show multi feedback confirmation table
	 *
	 * @param
	 * @return
	 */
	function showMultiFeedbackConfirmationTableObject()
	{
		global $ilTabs, $tpl;
		
		$this->addSubTabs("assignment");
		
		$ass = new ilExAssignment((int) $_GET["ass_id"]);
		include_once("./Modules/Exercise/classes/class.ilFeedbackConfirmationTable2GUI.php");
		$tab = new ilFeedbackConfirmationTable2GUI($this, "showMultiFeedbackConfirmationTable", $ass);
		$tpl->setContent($tab->getHTML());		
	}
	
	/**
	 * Cancel Multi Feedback
	 */
	function cancelMultiFeedbackObject()
	{
		$ass = new ilExAssignment((int) $_GET["ass_id"]);
		$ass->clearMultiFeedbackDirectory();
		
		$this->ctrl->redirect($this, "members");
	}
	
	/**
	 * Save multi feedback
	 */
	function saveMultiFeedbackObject()
	{
		$ass = new ilExAssignment((int) $_GET["ass_id"]);
		$ass->saveMultiFeedbackFiles($_POST["file"]);
		
		ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
		$this->ctrl->redirect($this, "members");
	}
	
	/**
	 * Save comment for learner (asynch)
	 */
	function saveCommentForLearnersObject()
	{		
		$res = array("result"=>false);
		
		if($this->ctrl->isAsynch())
		{
			$ass_id = (int)$_POST["ass_id"];
			$user_id = (int)$_POST["mem_id"];
			$comment = trim($_POST["comm"]);
			
			if($ass_id && $user_id)
			{				
				// team upload?
				if(is_object($this->ass) && $this->ass->getType() == ilExAssignment::TYPE_UPLOAD_TEAM)
				{
					$team_id = $this->ass->getTeamId($user_id);
					$user_ids = $this->ass->getTeamMembers($team_id);		
				}
				else
				{
					$user_ids = array($user_id);
				}				
				
				$all_members = new ilExerciseMembers($this->exercise);
				$all_members = $all_members->getMembers();
				
				$reci_ids = array();
				foreach($user_ids as $user_id)
				{
					if(in_array($user_id, $all_members))
					{
						$member_status = $this->ass->getMemberStatus($user_id);
						$member_status->setComment(ilUtil::stripSlashes($comment));
						$member_status->update();
						
						if(trim($comment))
						{
							$reci_ids[] = $user_id;
						}
					}
				}
				
				if(sizeof($reci_ids))
				{
					// send notification
					$this->exercise->sendFeedbackFileNotification(null, $reci_ids, 
						$ass_id, true);
				}
				
				$res = array("result"=>true, "snippet"=>ilUtil::shortenText($comment, 25, true));
			}						
		}				
		
		echo(json_encode($res));		
		exit();
	}	
	
	
	/**
	* update data of members table
	*/
	function updateMembersObject()
	{
		global $rbacsystem;
	
		$this->checkPermission("write");
	
		if ($_POST["downloadReturned"])
		{
			$this->object->members_obj->deliverReturnedFiles(key($_POST["downloadReturned"]));
			exit;
		}
		else
		{
			switch($_POST["action"])
			{
				case "save_status":
					$this->saveStatusObject();
					break;
					
				case "send_member":
					$this->sendMembersObject();
					break;
				
				case "redirectFeedbackMail":
					$this->redirectFeedbackMailObject();
					break;
					
				case "delete_member":
					$this->deassignMembersObject();
					break;
			}
		}
	}

	
	
	/**
	 * Export as excel
	 */
	function exportExcelObject()
	{
		$this->exercise->exportGradesExcel();
		exit;
	}
}

