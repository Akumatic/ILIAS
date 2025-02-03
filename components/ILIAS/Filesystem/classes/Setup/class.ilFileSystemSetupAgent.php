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

use ILIAS\Setup;
use ILIAS\Refinery;
use ILIAS\Data;
use ILIAS\UI;

class ilFileSystemSetupAgent implements Setup\Agent
{
    use Setup\Agent\HasNoNamedObjective;

    protected Refinery\Factory $refinery;

    public function __construct(
        Refinery\Factory $refinery
    ) {
        $this->refinery = $refinery;
    }

    /**
     * @inheritdoc
     */
    public function hasConfig(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getArrayToConfigTransformation(): Refinery\Transformation
    {
        return $this->refinery->custom()->transformation(fn($data): \ilFileSystemSetupConfig => new \ilFileSystemSetupConfig(
            $data["data_dir"]
        ));
    }

    /**
     * @inheritdoc
     */
    public function getInstallObjective(?Setup\Config $config = null): Setup\Objective
    {
        /** @noinspection PhpParamsInspection */
        return new ilFileSystemDirectoriesCreatedObjective($config);
    }

    /**
     * @inheritdoc
     */
    public function getUpdateObjective(?Setup\Config $config = null): Setup\Objective
    {
        if ($config) {
            /** @noinspection PhpParamsInspection */
            return new ilFileSystemConfigNotChangedObjective($config);
        }
        return new Setup\Objective\NullObjective();
    }

    /**
     * @inheritdoc
     */
    public function getBuildObjective(): Setup\Objective
    {
        return new Setup\Objective\NullObjective();
    }

    /**
     * @inheritdoc
     */
    public function getStatusObjective(Setup\Metrics\Storage $storage): Setup\Objective
    {
        return new ilFileSystemMetricsCollectedObjective($storage);
    }

    /**
     * @inheritDoc
     */
    public function getMigrations(): array
    {
        return [];
    }
}
