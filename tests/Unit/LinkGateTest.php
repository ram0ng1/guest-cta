<?php

declare(strict_types=1);

namespace Ramon\GuestCta\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Ramon\GuestCta\Formatter\ConfigureGuestLinks;
use Ramon\GuestCta\Formatter\LinkGate;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Renderer;

/**
 * The gate is what keeps a link's address from ever reaching a guest, so these
 * run real posts through the real parser and renderer rather than hand-written
 * XML: an address that survives anywhere in the output is a leak.
 */
#[Group('security')]
final class LinkGateTest extends TestCase
{
    private const FORUM = 'forum.test';
    private const PLACEHOLDER = '<span class="GuestCtaLink" role="button" tabindex="0">'
        .'<i class="fas fa-lock" aria-hidden="true"></i><span class="GuestCtaLink-label">Log in to view</span></span>';

    private static Parser $parser;
    private static Renderer $renderer;

    public static function setUpBeforeClass(): void
    {
        $configurator = new Configurator();
        $configurator->Autoemail;
        $configurator->Autolink;
        $configurator->Litedown;
        $configurator->BBCodes->addFromRepository('URL');
        $configurator->BBCodes->addFromRepository('QUOTE');

        (new ConfigureGuestLinks())($configurator);

        ['parser' => self::$parser, 'renderer' => self::$renderer] = $configurator->finalize();
        self::$renderer->setParameter(ConfigureGuestLinks::LABEL_PARAMETER, 'Log in to view');
    }

    private function gated(string $text): string
    {
        return self::$renderer->render(LinkGate::gate(self::$parser->parse($text), self::FORUM));
    }

    public function test_a_post_without_links_is_returned_untouched(): void
    {
        $xml = self::$parser->parse("Just **words**, nothing to hide.\n\nSecond paragraph 😀");

        self::assertSame($xml, LinkGate::gate($xml, self::FORUM));
    }

    public function test_a_bare_address_is_replaced_whole(): void
    {
        self::assertSame(
            '<p>see '.self::PLACEHOLDER.' for more</p>',
            $this->gated('see https://secret.example/path?token=abc for more')
        );
    }

    public function test_a_link_with_its_own_words_keeps_them(): void
    {
        self::assertSame(
            '<p>read <strong>the docs</strong> ('.self::PLACEHOLDER.') first</p>',
            $this->gated('read [**the docs**](https://secret.example/docs) first')
        );
    }

    public function test_a_linked_image_is_replaced_whole(): void
    {
        self::assertSame(
            '<p>'.self::PLACEHOLDER.'</p>',
            $this->gated('[![](https://forum.test/a.png)](https://secret.example/landing)')
        );
    }

    public function test_an_email_address_is_replaced_whole(): void
    {
        self::assertSame('<p>write to '.self::PLACEHOLDER.' today</p>', $this->gated('write to someone@secret.example today'));
    }

    public function test_links_to_the_forum_itself_are_left_alone(): void
    {
        $html = $this->gated('see https://FORUM.test/d/12-title and [here](/d/3) and [there](http://forum.test/t/x)');

        self::assertStringNotContainsString('GuestCtaLink', $html);
        self::assertSame(3, substr_count($html, '<a href='));
    }

    public function test_every_link_in_a_post_is_gated_including_quoted_ones(): void
    {
        $html = $this->gated(
            "[quote]as seen on https://secret.example/one[/quote]\n\n"
            ."[url=https://secret.example/two]two[/url] and <https://secret.example/three> and https://forum.test/ok"
        );

        self::assertSame(3, substr_count($html, 'GuestCtaLink-label'));
        self::assertSame(1, substr_count($html, '<a href='));
    }

    /**
     * Whatever shape the link takes, no part of where it went may be left
     * anywhere in what the guest receives.
     */
    #[DataProvider('externalLinks')]
    public function test_the_address_never_reaches_the_output(string $text): void
    {
        $html = $this->gated($text);

        self::assertStringNotContainsString('secret.example', $html);
        self::assertStringNotContainsString('<a ', $html);
        self::assertStringContainsString('GuestCtaLink', $html);
    }

    /** @return iterable<string, array{string}> */
    public static function externalLinks(): iterable
    {
        yield 'bare address' => ['https://secret.example/a'];
        yield 'http' => ['http://secret.example/a'];
        yield 'www autolink' => ['[x](http://www.secret.example)'];
        yield 'markdown link' => ['[click here](https://secret.example/a "a title")'];
        yield 'markdown autolink' => ['<https://secret.example/a>'];
        yield 'bbcode' => ['[url=https://secret.example/a]click[/url]'];
        yield 'bbcode, bare' => ['[url]https://secret.example/a[/url]'];
        yield 'reference link' => ["[click][1]\n\n[1]: https://secret.example/a"];
        yield 'forum host as a subdomain of another' => ['[x](https://forum.test.secret.example/)'];
        yield 'forum host as userinfo' => ['[x](https://forum.test@secret.example/)'];
        yield 'forum host in the path' => ['[x](https://secret.example/forum.test)'];
        yield 'two in one sentence' => ['https://secret.example/a and [b](https://secret.example/b)'];
        yield 'beside an emoji' => ['😀 [b](https://secret.example/b) 😀'];
    }

    public function test_text_around_a_link_survives_intact(): void
    {
        $html = $this->gated('Tom & "Jerry" <3 😀 → [their site](https://secret.example) — até já');

        self::assertSame(
            '<p>Tom &amp; "Jerry" &lt;3 😀 → their site ('.self::PLACEHOLDER.') — até já</p>',
            html_entity_decode($html, ENT_NOQUOTES | ENT_HTML5) === $html ? $html : preg_replace('/&#(\d+);/', '', $html) ?? $html
        );
    }

    /** @return iterable<string, array{string, bool}> */
    public static function addresses(): iterable
    {
        yield 'another site' => ['https://example.com/', true];
        yield 'protocol-relative' => ['//example.com/x', true];
        yield 'same host' => ['https://forum.test/d/1', false];
        yield 'same host, other case' => ['HTTPS://Forum.Test/d/1', false];
        yield 'same host, other scheme' => ['http://forum.test/', false];
        yield 'same host with a port' => ['https://forum.test:8443/', false];
        yield 'root-relative path' => ['/d/1', false];
        yield 'relative path' => ['d/1', false];
        yield 'fragment' => ['#p3', false];
        yield 'empty' => ['', false];
        yield 'mailto' => ['mailto:a@example.com', true];
        yield 'tel' => ['tel:+5511999999999', true];
        yield 'subdomain of the forum' => ['https://cdn.forum.test/x', true];
        yield 'forum as a subdomain' => ['https://forum.test.evil.example/', true];
        yield 'forum as userinfo' => ['https://forum.test@evil.example/', true];
        yield 'backslash host trick' => ['https://evil.example\\@forum.test/', true];
        yield 'unparseable' => ['http:///x', true];
    }

    #[DataProvider('addresses')]
    public function test_is_external(string $url, bool $expected): void
    {
        self::assertSame($expected, LinkGate::isExternal($url, self::FORUM));
    }

    public function test_xml_that_will_not_parse_still_loses_its_links(): void
    {
        $broken = '<r><p><URL url="https://secret.example/a">label</URL><unclosed></p></r>';

        self::assertSame('<r><p>label<unclosed></p></r>', LinkGate::gate($broken, self::FORUM));
    }
}
