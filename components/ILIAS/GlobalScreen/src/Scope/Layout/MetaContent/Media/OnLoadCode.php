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

declare(strict_types=1);

namespace ILIAS\GlobalScreen\Scope\Layout\MetaContent\Media;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class OnLoadCode extends AbstractMedia
{
    public function __construct(string $content, string $version, private int $batch = 2)
    {
        parent::__construct($content, $version);
    }


    public function getBatch(): int
    {
        return $this->batch;
    }

    #[\Override]
    public function getContent(): string
    {
        return 'try { ' . parent::getContent() . ' } catch (e) { console.log(e); }';
    }
}
