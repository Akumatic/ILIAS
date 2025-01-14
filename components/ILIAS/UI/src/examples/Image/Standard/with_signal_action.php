<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Image\Standard;

/**
 * ---
 * description: >
 *   Example for rendering an Image with a signal as action
 *
 * expected output: >
 *   Clicking onto the rendered image will open a modal.
 * ---
 */
function with_signal_action()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Generating and rendering the image and modal
    $image_in_modal = $f->image()->standard(
        "assets/ui-examples/images/Image/mountains.jpg",
        ""
    );
    $page = $f->modal()->lightboxImagePage($image_in_modal, "Nice view");
    $modal = $f->modal()->lightbox($page);

    $image = $f->image()->standard(
        "assets/ui-examples/images/Image/HeaderIconLarge.svg",
        "Thumbnail Example"
    )->withAction($modal->getShowSignal());

    $html = $renderer->render([$image, $modal]);

    return $html;
}
