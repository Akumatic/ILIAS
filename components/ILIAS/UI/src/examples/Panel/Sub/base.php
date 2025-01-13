<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Panel\Sub;

/**
 * ---
 * description: >
 *   Example for rendering a sub panel.
 *
 * expected output: >
 *   ILIAS shows a standard panel including a sub panel.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $block = $f->panel()->standard(
        "Panel Title",
        $f->panel()->sub("Sub Panel Title", $f->legacy("Some Content"))
    );

    return $renderer->render($block);
}
