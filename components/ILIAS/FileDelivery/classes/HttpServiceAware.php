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

namespace ILIAS\FileDelivery;

/**
 * Trait HttpServiceAware
 *
 * This trait provide a convenient way to consume the global http state
 * and aids to reduce code duplication.
 *
 * Please only use this trait if you need the global http state from a
 * static context! Otherwise consider to pass the http global state via constructor (DI).
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 * @version 1.0
 * @since   5.3
 *
 * @Internal
 */
trait HttpServiceAware
{
    /**
     * @var mixed|null
     */
    private static $http;


    /**
     * Fetches the global http state from ILIAS.
     *
     * The GlobalHttpStore is stored after the first
     * invocation.
     *
     * @return mixed|null The current http global state of ILIAS.
     * @since 5.3
     */
    protected static function http()
    {
        if (self::$http === null) {
            self::$http = $GLOBALS['DIC']['http'];
        }

        return self::$http;
    }
}
