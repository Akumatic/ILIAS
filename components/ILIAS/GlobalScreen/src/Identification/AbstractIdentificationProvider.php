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

namespace ILIAS\GlobalScreen\Identification;

use ILIAS\GlobalScreen\Identification\Map\IdentificationMap;
use ILIAS\GlobalScreen\Identification\Serializer\SerializerInterface;
use ILIAS\GlobalScreen\Provider\Provider;

/**
 * Class AbstractIdentificationProvider
 * @package ILIAS\GlobalScreen\Identification
 */
abstract class AbstractIdentificationProvider implements IdentificationProviderInterface
{
    protected string $class_name = '';
    protected static array $instances = [];

    /**
     * CoreIdentificationProvider constructor.
     * @param Provider            $provider
     * @param SerializerInterface $serializer
     * @param IdentificationMap   $map
     */
    public function __construct(protected Provider $provider, protected SerializerInterface $serializer, protected IdentificationMap $map)
    {
        $this->class_name = $this->provider::class;
    }

    /**
     * @param string $serialized_string
     * @return IdentificationInterface
     */
    public function fromSerializedString(string $serialized_string): IdentificationInterface
    {
        if ($this->map->isInMap($serialized_string)) {
            return $this->map->getFromMap($serialized_string);
        }
        /** @noinspection PhpParamsInspection */
        $identification = $this->serializer->unserialize($serialized_string);
        $this->map->addToMap($identification);

        return $identification;
    }
}
