<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Dropzone\File\Wrapper;

/**
 * ---
 * description: >
 *   Example for rendering a file dropzone wrapper.
 *
 * expected output: >
 *   ILIAS shows a blue box titled "Drag and drop files onto me!". If you drag a file into the box a small window opens.
 *   The file will be shown too. You can remove an uploaded file by clicking onto the "X" on the right side.
 * ---
 */
function base()
{
    global $DIC;

    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $request = $DIC->http()->request();
    $wrapper = $DIC->http()->wrapper()->query();

    $submit_flag = 'dropzone_wrapper_base';
    $post_url = "{$request->getUri()}&$submit_flag";

    $dropzone = $factory
        ->dropzone()->file()->wrapper(
            'Upload your files here',
            $post_url,
            $factory->messageBox()->info('Drag and drop files onto me!'),
            $factory->input()->field()->file(
                new \ilUIAsyncDemoFileUploadHandlerGUI(),
                'Your files'
            )
        );

    // please use ilCtrl to generate an appropriate link target
    // and check it's command instead of this.
    if ($wrapper->has($submit_flag)) {
        $dropzone = $dropzone->withRequest($request);
        $data = $dropzone->getData();
    } else {
        $data = 'no results yet.';
    }

    return '<pre>' . print_r($data, true) . '</pre>' .
        $renderer->render($dropzone);
}
