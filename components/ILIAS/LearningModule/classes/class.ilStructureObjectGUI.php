<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

use ILIAS\UI\Component\Input\Container\Form;
use ILIAS\LearningModule\Editing\EditSubObjectsGUI;

/**
 * @ilCtrl_Calls ilStructureObjectGUI: ilConditionHandlerGUI, ilObjectMetaDataGUI
 * @ilCtrl_Calls ilStructureObjectGUI: ILIAS\LearningModule\Editing\EditSubObjectsGUI
 */
class ilStructureObjectGUI extends ilLMObjectGUI
{
    protected \ILIAS\LearningModule\InternalDomainService $domain;
    protected \ILIAS\LearningModule\InternalGUIService $gui;
    protected ilPropertyFormGUI $form;
    protected ilConditionHandlerGUI $condHI;
    protected ilObjUser $user;
    protected ilTabsGUI $tabs;
    protected ilLogger $log;
    public ilLMTree $tree;

    public function __construct(
        ilObjLearningModule $a_content_obj,
        ilLMTree $a_tree
    ) {
        global $DIC;

        $this->user = $DIC->user();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->tabs = $DIC->tabs();
        $this->log = $DIC["ilLog"];
        $this->tpl = $DIC->ui()->mainTemplate();
        parent::__construct($a_content_obj);
        $this->tree = $a_tree;
        $this->gui = $DIC->learningModule()->internal()->gui();
        $this->domain = $DIC->learningModule()->internal()->domain();
    }

    public function setStructureObject(
        ilStructureObject $a_st_object
    ): void {
        $this->obj = $a_st_object;
    }

    public function getType(): string
    {
        return "st";
    }

    public function executeCommand(): void
    {
        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd();

        switch ($next_class) {
            case 'ilobjectmetadatagui':

                $this->setTabs();

                $md_gui = new ilObjectMetaDataGUI($this->content_object, $this->obj->getType(), $this->obj->getId());
                $md_gui->addMDObserver($this->obj, 'MDUpdateListener', 'General');
                $md_gui->addMDObserver($this->obj, 'MDUpdateListener', 'Educational'); // #9510
                $this->ctrl->forwardCommand($md_gui);
                break;

            case "ilconditionhandlergui":
                $ilTabs = $this->tabs;

                $this->setTabs();
                $this->initConditionHandlerInterface();
                $this->ctrl->forwardCommand($this->condHI);
                $ilTabs->setTabActive('preconditions');
                break;

            case strtolower(EditSubObjectsGUI::class):
                $this->setTabs();
                if ($this->request->getSubType() === "pg") {
                    $this->addSubTabs("sub_pages");
                    $table_title = $this->lng->txt("cont_pages");
                } else {
                    $this->addSubTabs("sub_chapters");
                    $table_title = $this->lng->txt("cont_subchapters");
                }
                $gui = $this->gui->editing()->editSubObjectsGUI(
                    $this->request->getSubType(),
                    $this->content_object,
                    $table_title
                );
                $this->ctrl->forwardCommand($gui);
                break;

            default:
                if ($cmd == 'listConditions') {
                    $this->setTabs();
                    $this->initConditionHandlerInterface();
                    $this->condHI->executeCommand();
                } elseif (($cmd == "create") && ($this->requested_new_type == "pg")) {
                    $this->setTabs();
                    $pg_gui = new ilLMPageObjectGUI($this->content_object);
                    $pg_gui->executeCommand();
                } else {
                    $this->$cmd();
                }
                break;
        }
    }

    public function create(): void
    {
        if ($this->requested_obj_id != 0) {
            $this->setTabs();
        }
        parent::create();
    }

    public function edit(): void
    {
        $this->view();
    }

    public function view(): void
    {
        $this->ctrl->redirectByClass(EditSubObjectsGUI::class, "editPages");
    }

    /**
     * Save all titles of chapters/pages
     */
    public function saveAllTitles(): void
    {
        $ilCtrl = $this->ctrl;

        $titles = $this->request->getTitles();
        ilLMObject::saveTitles($this->content_object, $titles, $this->requested_transl);

        $this->tpl->setOnScreenMessage('success', $this->lng->txt("lm_save_titles"), true);
        $ilCtrl->redirect($this, "showHierarchy");
    }

    /**
     * display subchapters of structure object
     */
    public function subchap(): void
    {
        $ilCtrl = $this->ctrl;
        $ilUser = $this->user;

        $this->setTabs();

        $this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.structure_edit.html", "components/ILIAS/LearningModule");
        $num = 0;

        $this->tpl->setCurrentBlock("form");
        $this->ctrl->setParameter($this, "backcmd", "subchap");
        $this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
        $this->tpl->setVariable("HEADER_TEXT", $this->lng->txt("cont_subchapters"));
        $this->tpl->setVariable("CHECKBOX_TOP", ilTree::POS_FIRST_NODE);

        $cnt = 0;
        $childs = $this->tree->getChilds($this->obj->getId());
        foreach ($childs as $child) {
            if ($child["type"] != "st") {
                continue;
            }
            $this->tpl->setCurrentBlock("table_row");

            // checkbox
            $this->tpl->setVariable("CHECKBOX_ID", $child["obj_id"]);
            $this->tpl->setVariable("IMG_OBJ", ilUtil::getImagePath("standard/icon_st.svg"));

            // type
            $this->ctrl->setParameterByClass("ilStructureObjectGUI", "obj_id", $child["obj_id"]);
            $link = $this->ctrl->getLinkTargetByClass("ilStructureObjectGUI", "view");
            $this->tpl->setVariable("LINK_TARGET", $link);

            // title
            $this->tpl->setVariable(
                "TEXT_CONTENT",
                ilStructureObject::_getPresentationTitle(
                    $child["obj_id"],
                    ilLMObject::CHAPTER_TITLE,
                    $this->content_object->isActiveNumbering()
                )
            );

            $this->tpl->parseCurrentBlock();
            $cnt++;
        }

        if ($cnt == 0) {
            $this->tpl->setCurrentBlock("notfound");
            $this->tpl->setVariable("NUM_COLS", 3);
            $this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
            $this->tpl->parseCurrentBlock();
        }
        //else
        //{
        // SHOW VALID ACTIONS
        $this->tpl->setVariable("NUM_COLS", 3);
        $acts = array("delete" => "delete", "cutChapter" => "cut",
                "copyChapter" => "copyChapter");
        if ($ilUser->clipboardHasObjectsOfType("st")) {
            $acts["pasteChapter"] = "pasteChapter";
        }
        $this->showActions($acts);
        //}

        // SHOW POSSIBLE SUB OBJECTS
        $this->tpl->setVariable("NUM_COLS", 3);
        $subobj = array("st");
        $opts = ilLegacyFormElementsUtil::formSelect(12, "new_type", $subobj);
        $this->tpl->setCurrentBlock("add_object");
        $this->tpl->setVariable("SELECT_OBJTYPE", $opts);
        $this->tpl->setVariable("BTN_NAME", "create");
        $this->tpl->setVariable("TXT_ADD", $this->lng->txt("insert"));
        $this->tpl->parseCurrentBlock();

        $this->tpl->setCurrentBlock("form");
        $this->tpl->parseCurrentBlock();

        $ilCtrl->setParameter($this, "obj_id", $this->requested_obj_id);
    }

    public function save(): void
    {
        $form = $this->getCreateForm();

        if ($form->checkInput()) {
            $this->obj = new ilStructureObject($this->content_object);
            $this->obj->setType("st");
            $this->obj->setTitle($form->getInput("title"));
            $this->obj->setDescription($form->getInput("desc"));
            $this->obj->setLMId($this->content_object->getId());
            $this->obj->create();

            $this->putInTree();

            // check the tree
            $this->checkTree();
        }

        if ($this->requested_obj_id > 0) {
            $this->ctrl->redirect($this, "subchap");
        }
    }

    /**
     * put chapter into tree
     */
    public function putInTree(
        ?int $target = null
    ): void {
        $target = $this->requested_target;
        //echo "st:putInTree";
        // chapters should be behind pages in the tree
        // so if target is first node, the target is substituted with
        // the last child of type pg
        if ($target == ilTree::POS_FIRST_NODE) {
            $tree = new ilTree($this->content_object->getId());
            $tree->setTableNames('lm_tree', 'lm_data');
            $tree->setTreeTablePK("lm_id");

            // determine parent node id
            $parent_id = ($this->requested_obj_id > 0)
                ? $this->requested_obj_id
                : $tree->getRootId();
            // determine last child of type pg
            $childs = $tree->getChildsByType($parent_id, "pg");
            if (count($childs) != 0) {
                $target = $childs[count($childs) - 1]["obj_id"];
            }
        }
        if ($target == "") {
            $target = ilTree::POS_LAST_NODE;
        }

        parent::putInTree($target);
    }

    public function cutPage(): void
    {
        $this->cutItems();
    }

    public function copyPage(): void
    {
        $this->copyItems();
    }

    public function pastePage(): void
    {
        $ilUser = $this->user;

        if (!$ilUser->clipboardHasObjectsOfType("pg")) {
            throw new ilLMException($this->lng->txt("no_page_in_clipboard"));
        }

        $this->insertPageClip();
    }

    public function cutChapter(): void
    {
        $this->cutItems("subchap");
    }

    /**
     * copy a single chapter (selection)
     */
    public function copyChapter(): void
    {
        $this->copyItems("subchap");
    }

    public function pasteChapter(): void
    {
        $this->insertChapterClip(false, "subchap");
    }

    public function activatePages(): void
    {
        $lng = $this->lng;

        $ids = $this->request->getIds();
        if (count($ids) > 0) {
            $act_items = array();
            // get all "top" ids, i.e. remove ids, that have a selected parent
            foreach ($ids as $id) {
                $path = $this->tree->getPathId($id);
                $take = true;
                foreach ($path as $path_id) {
                    if ($path_id != $id && in_array($path_id, $ids)) {
                        $take = false;
                    }
                }
                if ($take) {
                    $act_items[] = $id;
                }
            }


            foreach ($act_items as $id) {
                $childs = $this->tree->getChilds($id);
                foreach ($childs as $child) {
                    if (ilLMObject::_lookupType($child["child"]) == "pg") {
                        $act = ilLMPage::_lookupActive(
                            $child["child"],
                            $this->content_object->getType()
                        );
                        ilLMPage::_writeActive(
                            $child["child"],
                            $this->content_object->getType(),
                            !$act
                        );
                    }
                }
                if (ilLMObject::_lookupType($id) == "pg") {
                    $act = ilLMPage::_lookupActive(
                        $id,
                        $this->content_object->getType()
                    );
                    ilLMPage::_writeActive(
                        $id,
                        $this->content_object->getType(),
                        !$act
                    );
                }
            }
        } else {
            $this->tpl->setOnScreenMessage('failure', $lng->txt("no_checkbox"), true);
        }

        $this->ctrl->redirect($this, "view");
    }

    public function initConditionHandlerInterface(): void
    {
        $this->condHI = new ilConditionHandlerGUI();
        $this->condHI->setBackButtons(array());
        $this->condHI->setAutomaticValidation(false);
        $this->condHI->setTargetType("st");
        $this->condHI->setTargetRefId($this->content_object->getRefId());
        $this->condHI->setTargetId($this->obj->getId());
        $this->condHI->setTargetTitle($this->obj->getTitle());
    }


    /**
     * cancel creation of new page or chapter
     */
    public function cancel(): void
    {
        if ($this->requested_obj_id != 0) {
            if ($this->requested_new_type == "pg") {
                $this->ctrl->redirect($this, "view");
            } else {
                $this->ctrl->redirect($this, "subchap");
            }
        }
    }

    public function setTabs(): void
    {
        $ilTabs = $this->tabs;
        $lng = $this->lng;

        // subelements
        $ilTabs->addTarget(
            "cont_pages_and_subchapters",
            $this->ctrl->getLinkTarget($this, 'showHierarchy'),
            array("view", "showHierarchy"),
            get_class($this)
        );

        // preconditions
        $ilTabs->addTarget(
            "preconditions",
            $this->ctrl->getLinkTarget($this, 'listConditions'),
            "listConditions",
            get_class($this)
        );

        // metadata
        $mdgui = new ilObjectMetaDataGUI($this->content_object, $this->obj->getType(), $this->obj->getId());
        $mdtab = $mdgui->getTab();
        if ($mdtab) {
            $ilTabs->addTarget(
                "meta_data",
                $mdtab,
                "",
                "ilmdeditorgui"
            );
        }

        $this->tpl->setTitleIcon(ilUtil::getImagePath("standard/icon_st.svg"));
        $this->tpl->setTitle(
            $this->lng->txt($this->obj->getType()) . ": " . $this->obj->getTitle()
        );

        // presentation view
        $ilTabs->addNonTabbedLink(
            "pres_mode",
            $lng->txt("cont_presentation_view"),
            ILIAS_HTTP_PATH . "/goto.php?target=st_" . $this->obj->getId(),
            "_top"
        );
    }

    protected function addSubTabs($active = "")
    {
        $ilTabs = $this->tabs;
        $lng = $this->lng;

        // content -> pages
        $this->ctrl->setParameterByClass(static::class, "sub_type", "pg");
        $ilTabs->addSubTab(
            "sub_pages",
            $lng->txt("cont_pages"),
            $this->ctrl->getLinkTargetByClass(EditSubObjectsGUI::class)
        );

        // chapters
        $this->ctrl->setParameterByClass(static::class, "sub_type", "st");
        $ilTabs->addSubTab(
            "sub_chapters",
            $lng->txt("cont_subchapters"),
            $this->ctrl->getLinkTargetByClass(EditSubObjectsGUI::class)
        );
        $ilTabs->activateSubTab($active);
    }

    /**
     * @throws ilPermissionException
     */
    public static function _goto(
        string $a_target,
        int $a_target_ref_id = 0
    ): void {
        global $DIC;
        $main_tpl = $DIC->ui()->mainTemplate();

        $lng = $DIC->language();
        $ilAccess = $DIC->access();
        $ctrl = $DIC->ctrl();

        // determine learning object
        $lm_id = ilLMObject::_lookupContObjID($a_target);

        // get all references
        $ref_ids = ilObject::_getAllReferences($lm_id);

        // always try passed ref id first
        if (in_array($a_target_ref_id, $ref_ids)) {
            $ref_ids = array_merge(array($a_target_ref_id), $ref_ids);
        }

        // check read permissions
        foreach ($ref_ids as $ref_id) {
            // Permission check
            if ($ilAccess->checkAccess("read", "", $ref_id)) {
                $ctrl->setParameterByClass("ilLMPresentationGUI", "obj_id", $a_target);
                $ctrl->setParameterByClass("ilLMPresentationGUI", "ref_id", $ref_id);
                $ctrl->redirectByClass("ilLMPresentationGUI", "");
            }
        }

        if ($ilAccess->checkAccess("read", "", ROOT_FOLDER_ID)) {
            $main_tpl->setOnScreenMessage('failure', sprintf(
                $lng->txt("msg_no_perm_read_item"),
                ilObject::_lookupTitle($lm_id)
            ), true);
            ilObjectGUI::_gotoRepositoryRoot();
        }

        throw new ilPermissionException($lng->txt("msg_no_perm_read_lm"));
    }


    ////
    //// Pages layout
    ////

    /**
     * Set layout for multipl pages
     */
    public function setPageLayout(): void
    {
        $tpl = $this->tpl;
        $ilCtrl = $this->ctrl;
        $lng = $this->lng;

        $ids = $this->request->getIds();
        if (count($ids) == 0) {
            $this->tpl->setOnScreenMessage('failure', $lng->txt("no_checkbox"), true);
            $ilCtrl->redirect($this, "showHierarchy");
        }

        $this->initSetPageLayoutForm();

        $tpl->setContent($this->form->getHTML());
    }

    /**
     * Init set page layout form.
     */
    public function initSetPageLayoutForm(): void
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;

        $this->form = new ilPropertyFormGUI();

        $ids = $this->request->getIds();
        foreach ($ids as $id) {
            $hi = new ilHiddenInputGUI("id[]");
            $hi->setValue($id);
            $this->form->addItem($hi);
        }
        $layout = ilObjContentObjectGUI::getLayoutOption(
            $lng->txt("cont_layout"),
            "layout",
            $this->content_object->getLayout()
        );

        $this->form->addItem($layout);

        $this->form->addCommandButton("savePageLayout", $lng->txt("save"));
        $this->form->addCommandButton("showHierarchy", $lng->txt("cancel"));

        $this->form->setTitle($lng->txt("cont_set_layout"));
        $this->form->setFormAction($ilCtrl->getFormAction($this));
    }

    /**
     * Save page layout
     */
    public function savePageLayout(): void
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;

        $ids = $this->request->getIds();
        $layout = $this->request->getLayout();
        foreach ($ids as $id) {
            ilLMPageObject::writeLayout(
                $id,
                $layout,
                $this->content_object
            );
        }
        $this->tpl->setOnScreenMessage('success', $lng->txt("msg_obj_modified"), true);
        $ilCtrl->redirect($this, "showHierarchy");
    }

    public function editMasterLanguage(): void
    {
        $ilCtrl = $this->ctrl;

        $ilCtrl->setParameter($this, "transl", "-");
        $ilCtrl->redirect($this, "showHierarchy");
    }

    public function switchToLanguage(): void
    {
        $ilCtrl = $this->ctrl;

        $ilCtrl->setParameter($this, "transl", $this->requested_totransl);
        $ilCtrl->redirect($this, "showHierarchy");
    }

    /**
     * Displays GUI to select template for page
     */
    public function insertTemplate(): void
    {
        $ctrl = $this->ctrl;
        $ui = $this->ui;
        $lng = $this->lng;

        $this->setTabs();
        $tabs = $this->tabs;
        $tabs->clearTargets();
        $tabs->setBackTarget($lng->txt("back"), $ctrl->getLinkTarget($this, "showHierarchy"));

        $ctrl->setParameter($this, "multi", ilChapterHierarchyFormGUI::getPostMulti());
        $ctrl->setParameter($this, "node_id", ilChapterHierarchyFormGUI::getPostNodeId());
        $ctrl->setParameter($this, "first_child", (int) ilChapterHierarchyFormGUI::getPostFirstChild());
        $ctrl->saveParameter($this, "obj_id");
        $form = $this->initInsertTemplateForm();
        $this->tpl->setContent($ui->renderer()->render($form) . ilLMPageObjectGUI::getLayoutCssFix());
    }

    public function initInsertTemplateForm(): Form\Standard
    {
        $ui = $this->ui;
        $f = $ui->factory();
        $ctrl = $this->ctrl;
        $lng = $this->lng;

        $fields["title"] = $f->input()->field()->text($lng->txt("title"), "");
        $ts = ilPageLayoutGUI::getTemplateSelection(ilPageLayout::MODULE_LM);
        if (!is_null($ts)) {
            $fields["layout_id"] = $ts;
        }

        // section
        $section1 = $f->input()->field()->section($fields, $lng->txt("cont_insert_pagelayout"));

        $form_action = $ctrl->getLinkTarget($this, "insertPageFromTemplate");
        return $f->input()->container()->form()->standard($form_action, ["sec" => $section1]);
    }

    /**
     * Insert (multiple) pages templates at node
     */
    public function insertPageFromTemplate(): void
    {
        global $DIC;

        $ilCtrl = $this->ctrl;

        $form = $this->initInsertTemplateForm();
        $form = $form->withRequest($DIC->http()->request());
        $data = $form->getData();
        $layout_id = $data["sec"]["layout_id"];
        $node_id = $this->request->getNodeId();
        $page_ids = ilLMPageObject::insertPagesFromTemplate(
            $this->content_object->getId(),
            $this->request->getMulti(),
            $node_id,
            $this->request->getFirstChild(),
            $layout_id,
            $data["sec"]["title"]
        );

        if ($this->request->getMulti() <= 1) {
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("lm_page_added"), true);
        } else {
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("lm_pages_added"), true);
        }

        //$ilCtrl->setParameter($this, "highlight", $page_ids);
        $ilCtrl->redirect($this, "showHierarchy", "node_" . $node_id);
    }
}
