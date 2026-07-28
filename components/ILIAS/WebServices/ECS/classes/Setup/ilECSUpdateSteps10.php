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
 */

declare(strict_types=1);

/**
 * Class ilECSUpdateSteps8
 * contains update steps for release 8
 * @author Stefan Meyer <meyer@leifos.de>
 */
class ilECSUpdateSteps10 implements ilDatabaseUpdateSteps
{
    protected ilDBInterface $db;

    public function prepare(ilDBInterface $db): void
    {
        $this->db = $db;
    }

    /**
     * Add consent table
     */
    public function step_1(): void
    {
        if (!$this->db->tableExists('ecs_course_assignments')) {
            return;
        }

        if ($this->db->tableColumnExists('ecs_course_assignments', 'cms_id')) {
            $this->db->modifyTableColumn('ecs_course_assignments', 'cms_id', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 255,
                'notnull' => true
            ]);
        }

        if ($this->db->tableColumnExists('ecs_course_assignments', 'cms_sub_id')) {
            $this->db->modifyTableColumn('ecs_course_assignments', 'cms_sub_id', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 255,
                'notnull' => false
            ]);
        }
    }
}
