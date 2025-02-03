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

namespace ILIAS\components\ResourceStorage\Resources\UI;

use ILIAS\UI\Factory;
use ILIAS\UI\Component\Item\Item;
use ILIAS\UI\Component\Card\Card;
use ILIAS\ResourceStorage\Resource\StorableResource;
use ILIAS\ResourceStorage\Revision\Revision;
use ILIAS\components\ResourceStorage\Resources\Listing\ViewDefinition;
use ILIAS\UI\Component\Table\PresentationRow;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class RevisionListingUI
{
    protected array $components = [];
    private Factory $ui_factory;
    private \ilLanguage $language;

    public function __construct(
        private ViewDefinition $view_definition,
        protected StorableResource $resource,
    ) {
        global $DIC;
        $this->language = $DIC->language();
        $this->language->loadLanguageModule('irss');
        $this->ui_factory = $DIC->ui()->factory();
        match ($view_definition->getMode()) {
            ViewDefinition::MODE_AS_TABLE => $this->initTable(),
            ViewDefinition::MODE_AS_ITEMS => $this->initItems(),
            ViewDefinition::MODE_AS_DECK => $this->initDeck(),
            default => $this->initTable(),
        };
    }

    private function initItems(): void
    {
        $this->components = array_map(function (Revision $revision): Item {
            $revision_to_component = new RevisionToComponent(
                $revision
            );
            $item = $revision_to_component->getAsItem(false);
            return $item->withLeadText($this->language->txt('revision') . ' ' . $revision->getVersionNumber());
        }, array_reverse($this->resource->getAllRevisionsIncludingDraft()));
    }

    private function initDeck(): void
    {
        $this->components[] = $this->ui_factory->deck(
            array_map(
                function (Revision $revision): Card {
                    $revision_to_component = new RevisionToComponent(
                        $revision
                    );
                    $card = $revision_to_component->getAsCard();
                    return $card->withTitle($this->prependRevisionNumberToTitle($revision, $card->getTitle()));
                },
                array_reverse($this->resource->getAllRevisionsIncludingDraft())
            )
        )->withSmallCardsSize();
    }

    private function initTable(): void
    {
        // Table
        $this->components[] = $this->ui_factory->table()->presentation(
            '',
            [],
            $this->getRowMapping()
        )->withData(
            array_reverse($this->resource->getAllRevisionsIncludingDraft())
        );
    }

    public function getRowMapping(): \Closure
    {
        return function (
            PresentationRow $row,
            Revision $revision
        ): PresentationRow {
            $revision_to_component = new RevisionToComponent(
                $revision
            );
            $row = $revision_to_component->getAsRowMapping()($row, $revision->getIdentification());
            /** @var PresentationRow $row */
            $title = $row->getHeadline();
            return $row->withHeadline(
                $this->prependRevisionNumberToTitle($revision, $title)
            );
        };
    }


    public function getComponents(): array
    {
        return $this->components;
    }

    public function prependRevisionNumberToTitle(Revision $revision, ?string $title): string
    {
        return $this->language->txt('revision') . ' ' . $revision->getVersionNumber() . ': ' . $title;
    }
}
