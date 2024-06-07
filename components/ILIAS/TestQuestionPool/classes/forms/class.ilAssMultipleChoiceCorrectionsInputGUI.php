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

/**
 * Class ilAssSingleChoiceCorrectionsInputGUI
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package components\ILIAS/Test(QuestionPool)
 */
class ilAssMultipleChoiceCorrectionsInputGUI extends ilMultipleChoiceWizardInputGUI
{
    /**
     * @var assSingleChoice
     */
    protected $qstObject;

    public function setValue($a_value): void
    {
        if (is_array($a_value)) {
            if (is_array($a_value['points']) && is_array($a_value['points_unchecked'])) {
                foreach ($this->values as $index => $value) {
                    $this->values[$index]->setPoints($a_value['points'][$index]);
                    $this->values[$index]->setPointsUnchecked($a_value['points_unchecked'][$index]);
                }
            }
        }
    }

    public function checkInput(): bool
    {
        global $DIC;
        $lng = $DIC['lng'];

        $foundvalues = $_POST[$this->getPostVar()];
        if (is_array($foundvalues)) {
            // check points
            $max = 0;
            if (is_array($foundvalues['points'])) {
                foreach ($foundvalues['points'] as $points) {
                    $points = str_replace(',', '.', $points);
                    if ($points > $max) {
                        $max = $points;
                    }
                    if (((strlen($points)) == 0) || (!is_numeric($points))) {
                        $this->setAlert($lng->txt("form_msg_numeric_value_required"));
                        return false;
                    }
                }
                foreach ($foundvalues['points_unchecked'] as $points) {
                    if ($points > $max) {
                        $max = $points;
                    }
                    if (((strlen($points)) == 0) || (!is_numeric($points))) {
                        $this->setAlert($lng->txt("form_msg_numeric_value_required"));
                        return false;
                    }
                }
            }
            if ($max == 0) {
                $this->setAlert($lng->txt("enter_enough_positive_points"));
                return false;
            }
        } else {
            $this->setAlert($lng->txt("msg_input_is_required"));
            return false;
        }

        return $this->checkSubItemsInput();
    }

    public function insert(ilTemplate $a_tpl): void
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        $lng = $DIC->language();

        $tpl = new ilTemplate("tpl.prop_multiplechoicecorrection_input.html", true, true, "components/ILIAS/TestQuestionPool");

        $i = 0;

        foreach ($this->values as $value) {
            if ($this->qstObject->isSingleline()) {
                if ($value->hasImage()) {
                    $imagename = $this->qstObject->getImagePathWeb() . $value->getImage();
                    if (($this->getSingleline()) && ($this->qstObject->getThumbSize())) {
                        if (@file_exists($this->qstObject->getImagePath() . $this->qstObject->getThumbPrefix() . $value->getImage())) {
                            $imagename = $this->qstObject->getImagePathWeb() . $this->qstObject->getThumbPrefix() . $value->getImage();
                        }
                    }

                    $tpl->setCurrentBlock('image');
                    $tpl->setVariable('SRC_IMAGE', $imagename);
                    $tpl->setVariable('IMAGE_NAME', $value->getImage());
                    $tpl->setVariable('ALT_IMAGE', ilLegacyFormElementsUtil::prepareFormOutput($value->getAnswertext()));
                    $tpl->parseCurrentBlock();
                } else {
                    $tpl->setCurrentBlock('image');
                    $tpl->touchBlock('image');
                    $tpl->parseCurrentBlock();
                }
            }

            $tpl->setCurrentBlock("answer");
            $tpl->setVariable("ANSWER", $value->getAnswertext());
            $tpl->parseCurrentBlock();

            $tpl->setCurrentBlock("row");
            $tpl->setVariable("POINTS_POST_VAR", $this->getPostVar());
            $tpl->setVariable("POINTS_ROW_NUMBER", $i);
            $tpl->setVariable(
                "PROPERTY_VALUE_CHECKED",
                ilLegacyFormElementsUtil::prepareFormOutput($value->getPointsChecked())
            );
            $tpl->setVariable(
                "PROPERTY_VALUE_UNCHECKED",
                ilLegacyFormElementsUtil::prepareFormOutput($value->getPointsUnchecked())
            );
            $tpl->parseCurrentBlock();

            $i++;
        }

        if ($this->qstObject->isSingleline()) {
            $tpl->setCurrentBlock("image_heading");
            $tpl->setVariable("ANSWER_IMAGE", $lng->txt('answer_image'));
            $tpl->setVariable("TXT_MAX_SIZE", ilFileUtils::getFileSizeInfo());
            $tpl->parseCurrentBlock();
        }

        $tpl->setCurrentBlock("points_heading");
        $tpl->setVariable("POINTS_CHECKED_TEXT", $lng->txt('points_checked'));
        $tpl->setVariable("POINTS_UNCHECKED_TEXT", $lng->txt('points_unchecked'));
        $tpl->parseCurrentBlock();

        $tpl->setVariable("ELEMENT_ID", $this->getPostVar());
        $tpl->setVariable("ANSWER_TEXT", $lng->txt('answer_text'));

        $a_tpl->setCurrentBlock("prop_generic");
        $a_tpl->setVariable("PROP_GENERIC", $tpl->get());
        $a_tpl->parseCurrentBlock();
    }
}
