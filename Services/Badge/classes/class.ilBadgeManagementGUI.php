<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
include_once("./Services/Badge/classes/class.ilBadgeHandler.php");

/**
 * Class ilBadgeManagementGUI
 * 
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id:$
 *
 * @package ServicesBadge
 */
class ilBadgeManagementGUI
{	
	protected $parent_ref_id; // [int]
	protected $parent_obj_id; // [int]
	protected $parent_obj_type; // [string]
		
	/**
	 * Construct
	 * 
	 * @param int $a_parent_ref_id
	 * @param int $a_parent_obj_id
	 * @param string $a_parent_obj_type
	 * @return self
	 */
	public function __construct($a_parent_ref_id, $a_parent_obj_id = null, $a_parent_obj_type = null)
	{
		global $lng;
		
		$this->parent_ref_id = $a_parent_ref_id;
		$this->parent_obj_id = $a_parent_obj_id
			? $a_parent_obj_id
			: ilObject::_lookupObjId($a_parent_ref_id);
		$this->parent_obj_type = $a_parent_obj_type 
			? $a_parent_obj_type
			: ilObject::_lookupType($this->parent_obj_id);

		if(!ilBadgeHandler::getInstance()->isObjectActive($this->parent_obj_id))
		{
			throw new ilException("inactive object");
		}
		
		$lng->loadLanguageModule("badge");
	}
	
	public function executeCommand()
	{
		global $ilCtrl;
		
		$next_class = $ilCtrl->getNextClass($this);
		$cmd = $ilCtrl->getCmd("listUsers");

		switch($next_class)
		{		
			default:	
				$this->$cmd();
				break;
		}
		
		return true;
	}
	
	protected function setTabs($a_active)
	{
		global $ilTabs, $lng, $ilCtrl;
		
		$ilTabs->addSubTab("users", 
			$lng->txt("users"),
			$ilCtrl->getLinkTarget($this, "listUsers"));
		
		$ilTabs->addSubTab("badges", 
			$lng->txt("obj_bdga"),
			$ilCtrl->getLinkTarget($this, "listBadges"));
		
		
		// :TODO: award badge(s)?
		
		$ilTabs->activateSubTab($a_active);
	}
	
	protected function hasWrite()
	{
		global $ilAccess;
		return $ilAccess->checkAccess("write", "", $this->parent_ref_id);
	}
	
	protected function listBadges()
	{
		global $ilToolbar, $lng, $ilCtrl, $tpl;
		
		$this->setTabs("badges");
		
		if($this->hasWrite())
		{
			$handler = ilBadgeHandler::getInstance();				
			$valid_types = $handler->getAvailableTypesForObjType($this->parent_obj_type);
			if($valid_types)
			{
				$options = array();
				foreach($valid_types as $id => $type)
				{
					$options[$id] = $type->getCaption();
				}
				asort($options);
				
				include_once "Services/Form/classes/class.ilSelectInputGUI.php";
				$drop = new ilSelectInputGUI($lng->txt("type"), "type");
				$drop->setOptions($options);
				$ilToolbar->addInputItem($drop, true);
				
				$ilToolbar->setFormAction($ilCtrl->getFormAction($this, "addBadge"));
				$ilToolbar->addFormButton($lng->txt("create"), "addBadge");
			}
		}
		
		include_once "Services/Badge/classes/class.ilBadgeTableGUI.php";
		$tbl = new ilBadgeTableGUI($this, "listBadges", $this->parent_obj_id, $this->hasWrite());
		$tpl->setContent($tbl->getHTML());
	}
	
	
	//
	// badge (CRUD)
	//
	
	protected function addBadge(ilPropertyFormGUI $a_form = null)
	{				
		global $ilCtrl, $tpl;
		
		$type_id = $_REQUEST["type"];
		if(!$type_id || 
			!$this->hasWrite())
		{
			$ilCtrl->redirect($this, "listBadges");
		}
		
		$ilCtrl->setParameter($this, "type", $type_id);
		
		$handler = ilBadgeHandler::getInstance();
		$type = $handler->getTypeInstanceByUniqueId($type_id);		
		if(!$type)
		{
			$ilCtrl->redirect($this, "listBadges");
		}
		
		if(!$a_form)
		{
			$a_form = $this->initBadgeForm("create", $type);
		}
		
		$tpl->setContent($a_form->getHTML());
	}
	
	protected function initBadgeForm($a_mode, ilBadgeType $a_type)
	{
		global $lng, $ilCtrl;
		
		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this, "saveBadge"));
		$form->setTitle($lng->txt("badge_badge").' "'.$a_type->getCaption().'"');
		
		$active = new ilCheckboxInputGUI($lng->txt("active"), "act");
		$form->addItem($active);
		
		$title = new ilTextInputGUI($lng->txt("title"), "title");
		$title->setRequired(true);
		$form->addItem($title);
		
		$desc = new ilTextAreaInputGUI($lng->txt("description"), "desc");
		$desc->setRequired(true);
		$form->addItem($desc);
		
		$options = array();
		if($a_mode == "create")
		{
			$options[""] = $lng->txt("please_select");
		}
		include_once "Services/Badge/classes/class.ilBadgeImageTemplate.php";
		foreach(ilBadgeImageTemplate::getInstances() as $tmpl)
		{
			$options[$tmpl->getId()] = $tmpl->getTitle();
		}
		
		$tmpl = new ilSelectInputGUI($lng->txt("badge_image_template_form"), "tmpl");
		$tmpl->setRequired(true);
		$tmpl->setOptions($options);
		$form->addItem($tmpl);
		
		$custom = $a_type->getConfigGUIInstance();
		if($custom &&
			$custom instanceof ilBadgeTypeGUI)
		{
			$custom->initConfigForm($form);
		}
		
		// :TODO: valid date/period		
		
		if($a_mode == "create")
		{
			$form->addCommandButton("saveBadge", $lng->txt("save"));
		}
		else
		{
			$form->addCommandButton("updateBadge", $lng->txt("save"));
		}
		$form->addCommandButton("listBadges", $lng->txt("cancel"));
		
		return $form;
	}
	
	protected function saveBadge()
	{
		global $ilCtrl, $lng;
		
		$type_id = $_REQUEST["type"];
		if(!$type_id || 
			!$this->hasWrite())
		{
			$ilCtrl->redirect($this, "listBadges");
		}
		
		$ilCtrl->setParameter($this, "type", $type_id);
		
		$handler = ilBadgeHandler::getInstance();
		$type = $handler->getTypeInstanceByUniqueId($type_id);		
		if(!$type)
		{
			$ilCtrl->redirect($this, "listBadges");
		}
		
		$form = $this->initBadgeForm("create", $type);		
		if($form->checkInput())
		{
			include_once "Services/Badge/classes/class.ilBadge.php";
			$badge = new ilBadge();
			$badge->setParentId($this->parent_obj_id); // :TODO: ref_id?
			$badge->setTypeId($type_id);
			$badge->setActive($form->getInput("act"));
			$badge->setTitle($form->getInput("title"));
			$badge->setDescription($form->getInput("desc"));
			$badge->setTemplateId($form->getInput("tmpl"));
						
			$custom = $type->getConfigGUIInstance();
			if($custom &&
				$custom instanceof ilBadgeTypeGUI)
			{
				$badge->setConfiguration($custom->getConfigFromForm($form));
			}
						
			$badge->create();
			
			ilUtil::sendInfo($lng->txt("settings_saved"), true);
			$ilCtrl->redirect($this, "listBadges");
		}
		
		$form->setValuesByPost();
		$this->addBadge($form);
	}
	
	protected function editBadge(ilPropertyFormGUI $a_form = null)
	{				
		global $ilCtrl, $tpl;
		
		$badge_id = $_REQUEST["bid"];
		if(!$badge_id || 
			!$this->hasWrite())
		{
			$ilCtrl->redirect($this, "listBadges");
		}
		
		$ilCtrl->setParameter($this, "bid", $badge_id);
		
		include_once "./Services/Badge/classes/class.ilBadge.php";
		$badge = new ilBadge($badge_id);
		
		if(!$a_form)
		{			
			$type = $badge->getTypeInstance();
			$a_form = $this->initBadgeForm("edit", $type);			
			$this->setBadgeFormValues($a_form, $badge, $type);
		}
		
		$tpl->setContent($a_form->getHTML());
	}
	
	protected function setBadgeFormValues(ilPropertyFormGUI $a_form, ilBadge $a_badge, ilBadgeType $a_type)
	{
		$a_form->getItemByPostVar("act")->setChecked($a_badge->isActive());
		$a_form->getItemByPostVar("title")->setValue($a_badge->getTitle());
		$a_form->getItemByPostVar("desc")->setValue($a_badge->getDescription());
		$a_form->getItemByPostVar("tmpl")->setValue($a_badge->getTemplateId());
		
		// :TODO: proper "preview"?
		include_once "Services/Badge/classes/class.ilBadgeImageTemplate.php";
		$tmpl = new ilBadgeImageTemplate($a_badge->getTemplateId());
		$a_form->getItemByPostVar("tmpl")->setInfo('<img src="'.$tmpl->getImagePath().'" style="max-width:250px; max-height:150px">');
		
		$custom = $a_type->getConfigGUIInstance();
		if($custom &&
			$custom instanceof ilBadgeTypeGUI)
		{
			$custom->importConfigToForm($a_form, $a_badge->getConfiguration());
		}		
	}
	
	protected function updateBadge()
	{
		global $ilCtrl, $lng;
		
		$badge_id = $_REQUEST["bid"];
		if(!$badge_id || 
			!$this->hasWrite())
		{
			$ilCtrl->redirect($this, "listBadges");
		}
		
		$ilCtrl->setParameter($this, "bid", $badge_id);
		
		include_once "./Services/Badge/classes/class.ilBadge.php";
		$badge = new ilBadge($badge_id);
		$type = $badge->getTypeInstance();
		$form = $this->initBadgeForm("update", $type);		
		if($form->checkInput())
		{			
			$badge->setActive($form->getInput("act"));
			$badge->setTitle($form->getInput("title"));
			$badge->setDescription($form->getInput("desc"));
			$badge->setTemplateId($form->getInput("tmpl"));
						
			$custom = $type->getConfigGUIInstance();
			if($custom &&
				$custom instanceof ilBadgeTypeGUI)
			{
				$badge->setConfiguration($custom->getConfigFromForm($form));
			}
						
			$badge->update();
			
			ilUtil::sendInfo($lng->txt("settings_saved"), true);
			$ilCtrl->redirect($this, "listBadges");
		}
		
		$form->setValuesByPost();
		$this->editBadge($form);
	}
	
	
	//
	// users
	// 
	
	protected function listUsers()
	{
		$this->setTabs("users");
		
		
		
	}
	
}