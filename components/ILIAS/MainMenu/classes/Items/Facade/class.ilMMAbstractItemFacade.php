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

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Identification\NullIdentification;
use ILIAS\GlobalScreen\Scope\MainMenu\Collector\Information\TypeInformation;
use ILIAS\GlobalScreen\Scope\MainMenu\Collector\MainMenuMainCollector as Main;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\hasSymbol;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\isChild;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\isInterchangeableItem;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\isItem;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\isTopItem;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\Item\Complex;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\Item\LinkList;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\Item\Lost;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\Item\RepositoryLink;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\Item\Separator;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\TopItem\TopLinkItem;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\TopItem\TopParentItem;
use ILIAS\UI\Component\Link\Link;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\isParent;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\hasTitle;

/**
 * Class ilMMAbstractItemFacade
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
abstract class ilMMAbstractItemFacade implements ilMMItemFacadeInterface
{
    protected bool $role_based_visibility = false;

    protected array $global_role_ids = [];

    protected TypeInformation $type_information;

    protected ilMMItemStorage $mm_item;
    protected isItem $filtered_item;
    protected isItem $raw_item;
    protected string $default_title = "-";

    /**
     * ilMMAbstractItemFacade constructor.
     * @param IdentificationInterface $identification
     * @param Main                    $collector
     * @throws Throwable
     */
    public function __construct(
        protected IdentificationInterface $identification,
        Main $collector
    ) {
        $this->raw_item = $collector->getSingleItemFromRaw($this->identification);
        $this->filtered_item = $collector->getSingleItemFromFilter($this->identification);
        $this->type_information = $collector->getTypeInformationCollection()->get($this->raw_item::class);
        $this->mm_item = ilMMItemStorage::register($this->raw_item);
    }

    public function getId(): string
    {
        return $this->identification->serialize();
    }

    /**
     * @return bool
     */
    public function hasStorage(): bool
    {
        return ilMMItemStorage::find($this->getId()) !== null;
    }

    /**
     * @inheritDoc
     */
    public function supportsRoleBasedVisibility(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasRoleBasedVisibility(): bool
    {
        return $this->role_based_visibility;
    }

    /**
     * @inheritDoc
     */
    public function setRoleBasedVisibility(bool $role_based_visibility): void
    {
        $this->role_based_visibility = $role_based_visibility;
    }

    /**
     * @inheritDoc
     */
    public function getGlobalRoleIDs(): array
    {
        return $this->global_role_ids;
    }

    /**
     * @inheritDoc
     */
    public function setGlobalRoleIDs(array $global_role_ids): void
    {
        $this->global_role_ids = $global_role_ids;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->mm_item->getIdentification() === '';
    }

    /**
     * @return ilMMItemStorage
     */
    public function itemStorage(): ilMMItemStorage
    {
        return $this->mm_item;
    }

    /**
     * @return IdentificationInterface
     */
    public function identification(): IdentificationInterface
    {
        return $this->identification;
    }

    public function getRawItem(): isItem
    {
        return $this->raw_item;
    }
    public function getFilteredItem(): isItem
    {
        return $this->filtered_item;
    }

    public function getAmountOfChildren(): int
    {
        if ($this->filtered_item instanceof isParent) {
            return count($this->filtered_item->getChildren());
        }

        return 0;
    }

    public function isAvailable(): bool
    {
        if ($this->filtered_item->isAvailable()) {
            return true;
        }
        return $this->filtered_item->isAlwaysAvailable();
    }

    /**
     * @inheritDoc
     */
    public function isActivated(): bool
    {
        if ($this->mm_item->isActive() && $this->getRawItem()->isAvailable()) {
            return true;
        }
        return $this->getRawItem()->isAlwaysAvailable();
    }

    /**
     * @inheritDoc
     */
    public function isAlwaysAvailable(): bool
    {
        return $this->getRawItem()->isAlwaysAvailable();
    }

    /**
     * @return string
     */
    public function getProviderNameForPresentation(): string
    {
        return $this->identification->getProviderNameForPresentation();
    }

    /**
     * @return string
     */
    public function getDefaultTitle(): string
    {
        $default_translation = ilMMItemTranslationStorage::getDefaultTranslation($this->identification);
        if ($default_translation !== "") {
            return $default_translation;
        }
        if ($this->default_title === "-" && $this->raw_item instanceof hasTitle) {
            $this->default_title = $this->raw_item->getTitle();
        }

        return $this->default_title;
    }

    /**
     * @param string $default_title
     */
    public function setDefaultTitle(string $default_title): void
    {
        $this->default_title = $default_title;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        global $DIC;
        if (!$this->raw_item->isAvailable() || $this->raw_item->isAlwaysAvailable()) {
            return $DIC->ui()->renderer()->render($this->raw_item->getNonAvailableReason());
        }

        return "";
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    public function getTypeForPresentation(): string
    {
        return $this->type_information->getTypeNameForPresentation();
    }

    public function getParentIdentificationString(): string
    {
        if ($this->getFilteredItem() instanceof isChild || $this->getFilteredItem() instanceof isInterchangeableItem) {
            $provider_name_for_presentation = $this->raw_item->getParent()->serialize();

            $storage_parent = $this->mm_item->getParentIdentification();
            if ($storage_parent !== $provider_name_for_presentation) {
                return $storage_parent;
            }

            return $provider_name_for_presentation;
        }

        return "";
    }

    /**
     * @return bool
     */
    public function isCustomType(): bool
    {
        $known_core_types = [
            Complex::class,
            Link::class,
            LinkList::class,
            Lost::class,
            RepositoryLink::class,
            Separator::class,
            TopLinkItem::class,
            TopParentItem::class,
        ];
        return !in_array($this->raw_item::class, $known_core_types, true);
    }

    /**
     * @inheritDoc
     */
    public function isTopItem(): bool
    {
        return $this->raw_item instanceof isTopItem;
    }

    public function isChild(): bool
    {
        $item = $this->getFilteredItem();
        return $item instanceof isChild
            || ($item instanceof isInterchangeableItem && $item->hasChanged());
    }

    /**
     * @inheritDoc
     */
    public function isInLostItem(): bool
    {
        if ($this->raw_item instanceof isChild) {
            return $this->raw_item->getParent() instanceof NullIdentification;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function setIsTopItm(bool $top_item): void
    {
        // TODO: Implement setIsTopItm() method.
    }

    /**
     * @inheritDoc
     */
    public function isInterchangeable(): bool
    {
        return $this->raw_item instanceof isInterchangeableItem;
    }

    /**
     * FSX check if doublette
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type_information->getType();
    }

    /**
     * @param string $parent
     */
    public function setParent(string $parent): void
    {
        $this->mm_item->setParentIdentification($parent);
    }

    /**
     * @inheritdoc
     */
    public function setPosition(int $position): void
    {
        $this->mm_item->setPosition($position);
    }

    /**
     * @param bool $status
     */
    public function setActiveStatus(bool $status): void
    {
        $this->mm_item->setActive($status);
    }

    public function supportsCustomIcon(): bool
    {
        return $this->raw_item instanceof hasSymbol;
    }

    public function getIconID(): ?string
    {
        return $this->mm_item->getIconId();
    }

    /**
     * @inheritDoc
     */
    public function setIconID(string $icon_id): void
    {
        $this->mm_item->setIconId($icon_id);
    }

    // CRUD

    public function update(): void
    {
        ilMMItemTranslationStorage::storeDefaultTranslation($this->identification, $this->default_title);
        $this->mm_item->update();
    }

    public function create(): void
    {
        ilMMItemTranslationStorage::storeDefaultTranslation($this->identification, $this->default_title);
        $this->mm_item->create();
        ilMMItemStorage::register($this->raw_item);
    }

    /**
     * deletes all translations associated with the current identification.
     * @throws Exception
     */
    protected function deleteAssociatedTranslations(): void
    {
        $ts = ilMMItemTranslationStorage::where([
            'identification' => $this->identification->serialize(),
        ], '=')->get();

        if (!empty($ts)) {
            foreach ($ts as $translation) {
                if ($translation instanceof ilMMItemTranslationStorage) {
                    $translation->delete();
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(): void
    {
        if ($this->isDeletable()) {
            $this->deleteAssociatedTranslations();
            $serialize = $this->identification->serialize();
            $mm = ilMMItemStorage::find($serialize);
            if ($mm instanceof ilMMItemStorage) {
                $mm->delete();
            }
        }
    }
}
