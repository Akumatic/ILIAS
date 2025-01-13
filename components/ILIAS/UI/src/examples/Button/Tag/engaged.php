<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Tag;

/**
 * ---
 * description: >
 *   Example for rendering an engaged Tag Button
 *
 * expected output: >
 *   ILIAS shows five buttons titled "simple tag". Clicking the button won't activate any actions.
 * ---
 */
function engaged()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $tag = $f->button()->tag("simple tag", "#")
                                  ->withEngagedState(true);

    $possible_relevances = array(
        $tag::REL_VERYLOW,
        $tag::REL_LOW,
        $tag::REL_MID,
        $tag::REL_HIGH,
        $tag::REL_VERYHIGH
    );

    foreach ($possible_relevances as $w) {
        $buffer[] = $renderer->render($tag->withRelevance($w));
    }

    return implode(' ', $buffer);
}
