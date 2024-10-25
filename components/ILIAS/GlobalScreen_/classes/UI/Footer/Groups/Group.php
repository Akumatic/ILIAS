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

namespace ILIAS\GlobalScreen\UI\Footer\Groups;

interface Group
{
    public function getId(): string;

    public function withId(string $id): self;

    public function getTitle(): string;

    public function withTitle(string $title): self;

    public function withActive(bool $active): self;

    public function isActive(): bool;

    public function withPosition(int $position): self;

    public function getPosition(): int;

    public function isCore(): bool;

    public function getItems(): int;
}
