<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Layout\Alignment\Horizontal\EvenlyDistributed;

/**
 * ---
 * expected output: >
 *   ILIAS shows colored text-blocks labeld A to F.
 *   The blocks are equal in size and distributed evenly across the available width,
 *   while A, B and C form a "virtual" block in itself, i.e. the size of the space
 *   consumed by A, B, C together equals the size of the remaining blocks.
 *   On shrinking the screen, ILIAS will try to keep this principle, meaning that
 *   A, B, C will break lines internally first, before, when the space does not allow
 *   for horizontal placement of all blocks next to each other anymore, all blocks
 *   are displayed vertically one after another.
 * ---
 */
function nested()
{
    global $DIC;
    $ui_factory = $DIC['ui.factory'];
    $renderer = $DIC['ui.renderer'];
    $tpl = $DIC['tpl'];
    $tpl->addCss('assets/ui-examples/css/alignment_examples.css');

    $blocks = [
        $ui_factory->legacy('<div class="example_block fullheight blue">D</div>'),
        $ui_factory->legacy('<div class="example_block fullheight green">E</div>'),
        $ui_factory->legacy('<div class="example_block fullheight yellow">F</div>')
    ];

    $aligned = $ui_factory->layout()->alignment()->horizontal()->evenlyDistributed(
        $ui_factory->legacy('<div class="example_block bluedark">A</div>'),
        $ui_factory->legacy('<div class="example_block greendark">B</div>'),
        $ui_factory->legacy('<div class="example_block yellowdark">C</div>')
    );

    return $renderer->render(
        $ui_factory->layout()->alignment()->horizontal()
            ->evenlyDistributed(
                $aligned,
                ...$blocks
            )
    );
}
