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

require_once("vendor/composer/vendor/autoload.php");
require_once(__DIR__ . "/../../Base.php");

use PHPUnit\Framework\MockObject\MockObject;
use ILIAS\UI\Component as C;
use ILIAS\UI\Implementation\Component as I;

class FooterTest extends ILIAS_UI_TestBase
{
    protected I\Link\Standard $link_mock;
    protected I\Symbol\Icon\Icon $icon_mock;
    protected I\Button\Shy $shy_mock;
    protected I\Listing\Unordered $unordered_mock;
    protected I\Button\Factory $button_factory;
    protected I\Link\Factory $link_factory;
    protected I\Listing\Factory $listing_factory;
    protected \ILIAS\Data\URI $uri_mock;
    protected string $link_html;
    protected string $icon_html;
    protected string $shy_html;
    protected string $unordered_html;

    protected function setUp(): void
    {
        $this->link_html = sha1(C\Link\Standard::class);
        $this->icon_html = sha1(C\Symbol\Icon\Icon::class);
        $this->shy_html = sha1(C\Button\Shy::class);
        $this->unordered_html = sha1(C\Listing\Unordered::class);

        $this->link_mock = $this->createMock(I\Link\Standard::class);
        $this->link_mock->method('getCanonicalName')->willReturn($this->link_html);

        $this->icon_mock = $this->createMock(I\Symbol\Icon\Icon::class);
        $this->icon_mock->method('getCanonicalName')->willReturn($this->icon_html);

        $this->shy_mock = $this->createMock(I\Button\Shy::class);
        $this->shy_mock->method('getCanonicalName')->willReturn($this->shy_html);

        $this->unordered_mock = $this->createMock(I\Listing\Unordered::class);
        $this->unordered_mock->method('getCanonicalName')->willReturn($this->unordered_html);

        $this->button_factory = $this->createMock(I\Button\Factory::class);
        $this->button_factory->method('shy')->willReturn($this->shy_mock);

        $this->link_factory = $this->createMock(I\Link\Factory::class);
        $this->link_factory->method('standard')->willReturn($this->link_mock);

        $this->listing_factory = $this->createMock(I\Listing\Factory::class);
        $this->listing_factory->method('unordered')->willReturn($this->unordered_mock);

        $this->uri_mock = $this->createMock(\ILIAS\Data\URI::class);

        parent::setUp();
    }

    public function testSetAndGetModalsWithTrigger(): void
    {
        $signal_mock = $this->createMock(C\Signal::class);

        $modal_mock = $this->createMock(C\Modal\RoundTrip::class);
        $modal_mock->method('getShowSignal')->willReturn($signal_mock);

        $shy_mock = $this->shy_mock;
        $shy_mock->expects($this->once())->method('withOnClick')->with($signal_mock)->willReturnSelf();

        $shy_mock = $shy_mock->withOnClick($modal_mock->getShowSignal());

        /** @var I\MainControls\Footer $footer */
        $footer = $this->getUIFactory()->mainControls()->footer();
        $footer = $footer->withAdditionalLink($shy_mock);
        $footer = $footer->withAdditionalModal($modal_mock);

        $this->assertCount(1, $footer->getModals());
        $this->assertCount(1, $footer->getAdditionalLinks());
    }

    public function testRenderWithPermanentUrl(): void
    {
        $footer = $this->getUIFactory()->mainControls()->footer();
        $footer = $footer->withPermanentURL($this->uri_mock);

        $this->button_factory->expects($this->once())->method('shy')->with('copy_perma_link', '');
        $this->shy_mock->expects($this->once())->method('withAdditionalOnLoadCode')->willReturnSelf();

        $renderer = $this->getDefaultRenderer(null, [$this->shy_mock]);
        $actual_html = $renderer->render($footer);

        $expected_html = <<<EOT
<footer class="c-maincontrols c-maincontrols__footer">
    <section class="c-maincontrols__footer-grid" data-section="permanent-link" aria-label="footer_permanent_link" tabindex="0">
        <div class="c-maincontrols__footer-grid__item text-left">
            <div class="c-tooltip__container c-tooltip--top" aria-live="polite">
                $this->shy_html
                <div class="c-tooltip c-tooltip--hidden" role="tooltip">
                    perma_link_copied
                </div>
            </div>
        </div>
    </section>
</footer>
EOT;

        $this->assertEquals(
            $this->brutallyTrimHTML($expected_html),
            $this->brutallyTrimHTML($actual_html)
        );
    }

    public function testRenderWithAdditionalLinkGroup(): void
    {
        $link_group_title = sha1('link_group_1');

        $footer = $this->getUIFactory()->mainControls()->footer();
        $footer = $footer->withAdditionalLinkGroup($link_group_title, [$this->link_mock]);

        $this->listing_factory->expects($this->once())->method('unordered')->with([$this->link_mock]);

        $renderer = $this->getDefaultRenderer(null, [$this->unordered_mock]);
        $actual_html = $renderer->render($footer);

        $expected_html = <<<EOT
<footer class="c-maincontrols c-maincontrols__footer">
    <section class="c-maincontrols__footer-grid" data-section="link-groups" aria-label="footer_link_groups" tabindex="0">
        <div class="c-maincontrols__footer-grid__item text-left">
            <strong>$link_group_title</strong>$this->unordered_html
        </div>
    </section>
</footer>
EOT;

        $this->assertEquals(
            $this->brutallyTrimHTML($expected_html),
            $this->brutallyTrimHTML($actual_html)
        );
    }

    public function testRenderWithAdditionalLink(): void
    {
        $footer = $this->getUIFactory()->mainControls()->footer();
        $footer = $footer->withAdditionalLink($this->link_mock);

        $renderer = $this->getDefaultRenderer(null, [$this->link_mock]);
        $actual_html = $renderer->render($footer);

        $expected_html = <<<EOT
<footer class="c-maincontrols c-maincontrols__footer">
    <section class="c-maincontrols__footer-grid" data-section="links" aria-label="footer_links" tabindex="0">
        <div class="c-maincontrols__footer-grid__item text-left">$this->link_html</div>
    </section>
</footer>
EOT;

        $this->assertEquals(
            $this->brutallyTrimHTML($expected_html),
            $this->brutallyTrimHTML($actual_html)
        );
    }

    public function testRenderWithAdditionalIcon(): void
    {
        $footer = $this->getUIFactory()->mainControls()->footer();
        $footer = $footer->withAdditionalIcon($this->icon_mock);

        $renderer = $this->getDefaultRenderer(null, [$this->icon_mock]);
        $actual_html = $renderer->render($footer);

        $expected_html = <<<EOT
<footer class="c-maincontrols c-maincontrols__footer">
    <section class="c-maincontrols__footer-grid" data-section="icons" aria-label="footer_icons" tabindex="0">
        <div class="c-maincontrols__footer-grid__item l-bar__group">
            <span class="l-bar__element">$this->icon_html</span>
        </div>
    </section>
</footer>
EOT;

        $this->assertEquals(
            $this->brutallyTrimHTML($expected_html),
            $this->brutallyTrimHTML($actual_html)
        );
    }

    public function testRenderWithAdditionalText(): void
    {
        $text = sha1('text_1');

        $footer = $this->getUIFactory()->mainControls()->footer();
        $footer = $footer->withAdditionalText($text);

        $renderer = $this->getDefaultRenderer();
        $actual_html = $renderer->render($footer);

        $expected_html = <<<EOT
<footer class="c-maincontrols c-maincontrols__footer">
    <section class="c-maincontrols__footer-grid" data-section="texts" aria-label="footer_texts" tabindex="0">
        <div class="c-maincontrols__footer-grid__item text-left">$text</div>
    </section>
</footer>
EOT;

        $this->assertEquals(
            $this->brutallyTrimHTML($expected_html),
            $this->brutallyTrimHTML($actual_html)
        );
    }

    public function testRenderWithModalsAndTrigger(): void
    {
        $modal_html = sha1(C\Modal\RoundTrip::class);
        $modal_mock = $this->createMock(C\Modal\RoundTrip::class);
        $modal_mock->method('getCanonicalName')->willReturn($modal_html);
        $modal_mock->method('getShowSignal')->willReturn(
            $this->createMock(C\Signal::class)
        );

        $shy_mock = $this->shy_mock;
        $shy_mock->method('withOnClick')->willReturnSelf();

        $shy_mock = $shy_mock->withOnClick($modal_mock->getShowSignal());

        $footer = $this->getUIFactory()->mainControls()->footer();
        $footer = $footer->withAdditionalLink($shy_mock);
        $footer = $footer->withAdditionalModal($modal_mock);

        $renderer = $this->getDefaultRenderer(null, [$modal_mock, $shy_mock]);
        $actual_html = $renderer->render($footer);

        $expected_html = <<<EOT
<footer class="c-maincontrols c-maincontrols__footer">
    <section class="c-maincontrols__footer-grid" data-section="links" aria-label="footer_links" tabindex="0">
        <div class="c-maincontrols__footer-grid__item text-left">$this->shy_html</div>
    </section>
</footer>$modal_html
EOT;

        $this->assertEquals(
            $this->brutallyTrimHTML($expected_html),
            $this->brutallyTrimHTML($actual_html)
        );
    }

    public function testRenderEmptyFooter(): void
    {
        $footer = $this->getUIFactory()->mainControls()->footer();

        $renderer = $this->getDefaultRenderer();
        $actual_html = $renderer->render($footer);

        $expected_html = '';

        $this->assertEquals($expected_html, $actual_html);
    }

    public function getUIFactory(): NoUIFactory
    {
        return new class (
            $this->createMock(I\SignalGeneratorInterface::class),
            $this->createMock(I\Counter\Factory::class),
            $this->createMock(I\Symbol\Factory::class),
            $this->button_factory,
            $this->link_factory,
            $this->listing_factory,
        ) extends NoUIFactory {
            public function __construct(
                protected I\SignalGeneratorInterface $signal_generator,
                protected I\Counter\Factory $counter_factory,
                protected I\Symbol\Factory $symbol_factory,
                protected I\Button\Factory $button_factory,
                protected I\Link\Factory $link_factory,
                protected I\Listing\Factory $listing_factory,
            ) {
            }
            public function mainControls(): I\MainControls\Factory
            {
                return new I\MainControls\Factory(
                    $this->signal_generator,
                    new I\MainControls\Slate\Factory(
                        $this->signal_generator,
                        $this->counter_factory,
                        $this->symbol_factory,
                    ),
                );
            }
            public function button(): I\Button\Factory
            {
                return $this->button_factory;
            }
            public function link(): I\Link\Factory
            {
                return $this->link_factory;
            }
            public function listing(): I\Listing\Factory
            {
                return $this->listing_factory;
            }
        };
    }
}
