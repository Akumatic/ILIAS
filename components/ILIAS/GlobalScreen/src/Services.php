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
/** @noinspection PhpIncompatibleReturnTypeInspection */

namespace ILIAS\GlobalScreen;

use ILIAS\GlobalScreen\Collector\CollectorFactory;
use ILIAS\GlobalScreen\Identification\IdentificationFactory;
use ILIAS\GlobalScreen\Provider\ProviderFactory;
use ILIAS\GlobalScreen\Scope\Layout\LayoutServices;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\MainMenuItemFactory;
use ILIAS\GlobalScreen\Scope\MetaBar\Factory\MetaBarItemFactory;
use ILIAS\GlobalScreen\Scope\Notification\NotificationServices;
use ILIAS\GlobalScreen\Scope\Toast\ToastServices;
use ILIAS\GlobalScreen\Scope\Tool\ToolServices;
use ILIAS\DI\UIServices;
use ILIAS\GlobalScreen\Scope\Footer\Factory\FooterItemFactory;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 *     TODO: Remove SingletonTrait
 */
class Services
{
    use SingletonTrait;

    private static ?Services $instance = null;
    private ToastServices $toast_services;

    public string $resource_version = '';

    /**
     * Services constructor.
     * @param ProviderFactory $provider_factory
     * @param string          $resource_version
     */
    public function __construct(
        private ProviderFactory $provider_factory,
        ?UIServices $ui = null,
        string $resource_version = ''
    ) {
        global $DIC;
        $this->resource_version = urlencode($resource_version);
        $this->toast_services = new ToastServices($ui ?? $DIC->ui());
    }

    public function mainBar(): MainMenuItemFactory
    {
        return $this->get(MainMenuItemFactory::class);
    }


    public function metaBar(): MetaBarItemFactory
    {
        return $this->get(MetaBarItemFactory::class);
    }

    public function footer(): FooterItemFactory
    {
        return $this->get(FooterItemFactory::class);
    }

    /**
     * @return ToolServices
     * @see ToolServices
     */
    public function tool(): ToolServices
    {
        return $this->get(ToolServices::class);
    }

    /**
     * @return LayoutServices
     */
    public function layout(): LayoutServices
    {
        return $this->getWithArgument(LayoutServices::class, $this->resource_version);
    }

    /**
     * @return NotificationServices
     */
    public function notifications(): NotificationServices
    {
        return $this->get(NotificationServices::class);
    }

    public function toasts(): ToastServices
    {
        return $this->toast_services;
    }

    /**
     * @return CollectorFactory
     */
    public function collector(): CollectorFactory
    {
        return $this->getWithArgument(CollectorFactory::class, $this->provider_factory);
    }

    /**
     * @return IdentificationFactory
     * @see IdentificationFactory
     */
    public function identification(): IdentificationFactory
    {
        return $this->getWithArgument(IdentificationFactory::class, $this->provider_factory);
    }
}
