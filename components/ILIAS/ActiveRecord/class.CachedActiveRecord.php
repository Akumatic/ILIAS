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
 * Class CachedActiveRecord
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 */
abstract class CachedActiveRecord extends ActiveRecord
{
    private string $_hash = '';

    final public function getCacheIdentifier(): string
    {
        if ($this->getArFieldList()->getPrimaryField()) {
            return ($this->getConnectorContainerName() . "_" . $this->getPrimaryFieldValue());
        }

        return "";
    }

    public function getTTL(): int
    {
        return 60;
    }

    /**
     * @inheritDoc
     */
    public function __construct(mixed $primary_key = 0, ?arConnector $arConnector = null)
    {
        if (is_null($arConnector)) {
            $arConnector = new arConnectorDB();
        }

        $arConnector = new arConnectorCache($arConnector);
        arConnectorMap::register($this, $arConnector);
        parent::__construct($primary_key);
    }

    public function afterObjectLoad(): void
    {
        parent::afterObjectLoad();
        $this->_hash = $this->buildHash();
    }

    private function buildHash(): string
    {
        $hashing = [];
        foreach ($this->getArFieldList()->getFields() as $arField) {
            $name = $arField->getName();
            $hashing[$name] = $this->{$name};
        }
        return md5(serialize($hashing));
    }

    #[\Override]
    public function create(): void
    {
        parent::create();
    }

    #[\Override]
    public function read(): void
    {
        parent::read();
        $this->_hash = $this->buildHash();
    }

    #[\Override]
    public function update(): void
    {
        if ($this->buildHash() !== $this->_hash) {
            parent::update();
        }
    }

    #[\Override]
    public function delete(): void
    {
        parent::delete(); // TODO: Change the autogenerated stub
    }
}
