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

use ILIAS\UI\Factory;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\GlobalScreen_\UI\Translator;
use ILIAS\Data\URI;
use ILIAS\UI\Component\Table\Ordering;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;
use ILIAS\GlobalScreen\Scope\MainMenu\Collector\Renderer\Hasher;
use ILIAS\UI\Component\Table\OrderingBinding;
use ILIAS\UI\Component\Table\OrderingRowBuilder;
use ILIAS\GlobalScreen_\UI\UIHelper;

class GroupsTable implements OrderingBinding
{
    use Hasher;
    use UIHelper;

    public const COLUMN_ACTIVE = 'active';
    public const COLUMN_TITLE = 'title';
    public const CLUMNS_ITEMS = 'items';
    private Factory $ui_factory;
    private ServerRequestInterface $request;
    private ?URLBuilderToken $id_token = null;
    private \ilCtrlInterface $ctrl;
    private ?URLBuilder $url_builder = null;

    public function __construct(
        private readonly GroupsRepository $repository,
        private readonly Translator $translator
    ) {
        global $DIC;
        $this->ui_factory = $DIC->ui()->factory();
        $this->request = $DIC->http()->request();
        $this->ctrl = $DIC->ctrl();
    }

    public function getRows(OrderingRowBuilder $row_builder, array $visible_column_ids): \Generator
    {
        $ok = $this->ok($this->ui_factory);
        $nok = $this->nok($this->ui_factory);

        foreach ($this->repository->all() as $group) {
            $row = $row_builder->buildOrderingRow(
                $this->hash($group->getId()),
                [
                    self::COLUMN_TITLE => $this->ui_factory->link()->standard(
                        $group->getTitle(),
                        $this->url_builder
                            ->withParameter($this->id_token, $this->hash($group->getId()))
                            ->buildURI()
                            ->withParameter('cmd', 'editEntries')
                    ),
                    self::COLUMN_ACTIVE => $group->isActive() ? $ok : $nok,
                    self::CLUMNS_ITEMS => $group->getItems(),
                ]
            );

            if ($group->isCore()) {
                $row = $row->withDisabledAction('delete')
                           ->withDisabledAction('move');
            }

            yield $row;
        }
    }

    public function get(URI $target): Ordering
    {
        $uri_builder = $this->initURIBuilder($target);

        return $this->ui_factory
            ->table()
            ->ordering(
                $this->translator->translate('groups'),
                [
                    self::COLUMN_TITLE => $this->ui_factory->table()->column()->link(
                        $this->translator->translate('title', 'group')
                    ),
                    self::COLUMN_ACTIVE => $this->ui_factory->table()->column()->statusIcon(
                        $this->translator->translate('active', 'group')
                    ),
                    self::CLUMNS_ITEMS => $this->ui_factory->table()->column()->text(
                        $this->translator->translate('items', 'group')
                    ),
                ],
                $this,
                $target
            )
            ->withRequest($this->request)
            ->withActions(
                [
                    'edit_entries' => $this->ui_factory->table()->action()->single(
                        $this->translator->translate('edit_entries', 'group'),
                        $uri_builder->withURI($target->withParameter('cmd', 'editEntries')),
                        $this->id_token
                    )->withAsync(false),

                    'edit' => $this->ui_factory->table()->action()->single(
                        $this->translator->translate('edit', 'group'),
                        $uri_builder->withURI($target->withParameter('cmd', 'edit')),
                        $this->id_token
                    )->withAsync(true),

                    'toggle_activation' => $this->ui_factory->table()->action()->standard(
                        $this->translator->translate('toggle_activation', 'group'),
                        $uri_builder->withURI($target->withParameter('cmd', 'toggleActivation')),
                        $this->id_token
                    )->withAsync(false),

                    'delete' => $this->ui_factory->table()->action()->standard(
                        $this->translator->translate('delete', 'group'),
                        $uri_builder->withURI($target->withParameter('cmd', 'confirmDelete')),
                        $this->id_token
                    )->withAsync(true),

                    /*'translate' => $this->ui_factory->table()->action()->single(
                        $this->translator->translate('translate', 'group'),
                        $uri_builder->withURI($target->withParameter('cmd', 'translate')),
                        $this->id_token
                    )->withAsync(false),*/
                ]
            );
    }

    protected function initURIBuilder(URI $target): URLBuilder
    {
        $this->url_builder = new URLBuilder(
            $target
        );

        // these are the query parameters this instance is controlling
        $query_params_namespace = ['gsfo'];
        [$this->url_builder, $this->id_token] = $this->url_builder->acquireParameters(
            $query_params_namespace,
            'group_id'
        );
        return $this->url_builder;
    }

    public function getToken(URI $target): ?URLBuilderToken
    {
        $this->initURIBuilder($target);

        return $this->id_token;
    }

}
