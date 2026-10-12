<?php

declare(strict_types=1);

namespace Ramon\GuestCta\Formatter;

use DOMDocument;
use DOMElement;
use DOMText;
use s9e\TextFormatter\Utils;

/**
 * Takes the links out of a parsed post before it is rendered.
 *
 * Works on the stored XML rather than on the rendered HTML, so the address is
 * gone from everything rendered for the reader — the API payload, the page's
 * preloaded document and the no-JS markup — instead of merely being hidden by
 * a script after it has already been delivered.
 */
final class LinkGate
{
    /**
     * Stands where a link was. Rendered by the template registered in
     * {@see ConfigureGuestLinks}.
     */
    public const TAG = 'GUESTCTALINK';

    /**
     * Tags that render as a link, and the attribute holding where it goes.
     */
    private const LINK_TAGS = ['URL' => 'url', 'EMAIL' => 'email'];

    /**
     * Cheap enough to run on every post, so the ones without a link never pay
     * for a DOM parse.
     */
    public static function hasLinks(string $xml): bool
    {
        return str_contains($xml, '<URL ') || str_contains($xml, '<EMAIL ');
    }

    /**
     * Replace every link that leaves the forum with a placeholder.
     *
     * A link whose text is only its own address is replaced whole. One the
     * writer gave words to keeps those words, with the placeholder after them,
     * so the sentence around it still reads.
     */
    public static function gate(string $xml, string $forumHost): string
    {
        if (! self::hasLinks($xml)) {
            return $xml;
        }

        $dom = new DOMDocument();

        if (! @$dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
            // Nothing stored should ever fail to parse, but if it does the
            // links must not go out untouched.
            return (string) preg_replace('(</?(?:URL|EMAIL)(?=[ />])[^>]*>)', '', $xml);
        }

        $changed = false;

        foreach (self::LINK_TAGS as $tagName => $attribute) {
            // The node list is live, and the loop below removes from it.
            foreach (iterator_to_array($dom->getElementsByTagName($tagName)) as $link) {
                /** @var DOMElement $link */
                $target = $link->getAttribute($attribute);

                if ($tagName === 'URL' && ! self::isExternal($target, $forumHost)) {
                    continue;
                }

                self::replace($dom, $link, $target);
                $changed = true;
            }
        }

        if (! $changed || ! $dom->documentElement) {
            return $xml;
        }

        // Same serialization the formatter's own XML helpers use.
        return Utils::encodeUnicodeSupplementaryCharacters((string) $dom->saveXML($dom->documentElement));
    }

    /**
     * Does this address lead away from the forum?
     *
     * Only the host is compared, the same test the reader's browser applies to
     * links that reach it some other way. Anything with a scheme but no web
     * host — `mailto:`, `tel:` — counts as leaving.
     */
    public static function isExternal(string $url, string $forumHost): bool
    {
        $url = trim($url);

        // Browsers read a backslash as a slash; parse_url() does not, and
        // would name the wrong host.
        if (str_contains($url, '\\')) {
            return true;
        }

        if (! preg_match('~^(?:https?:)?//~i', $url)) {
            // No host of its own: either a path on this forum, or a scheme
            // that does not address a site at all.
            return (bool) preg_match('~^[a-z][a-z0-9+.-]*:~i', $url);
        }

        $host = parse_url($url, PHP_URL_HOST);

        return ! is_string($host) || strcasecmp($host, $forumHost) !== 0;
    }

    private static function replace(DOMDocument $dom, DOMElement $link, string $target): void
    {
        $parent = $link->parentNode;

        if (! $parent) {
            return;
        }

        $placeholder = $dom->createElement(self::TAG);

        if (self::isBare($link, $target)) {
            $parent->replaceChild($placeholder, $link);

            return;
        }

        // The markup the writer typed comes along too — for a Markdown link
        // that includes the address — but the renderer never outputs it.
        while ($child = $link->firstChild) {
            $parent->insertBefore($child, $link);
        }

        $parent->insertBefore($dom->createTextNode(' ('), $link);
        $parent->insertBefore($placeholder, $link);
        $parent->insertBefore($dom->createTextNode(')'), $link);
        $parent->removeChild($link);
    }

    /**
     * Is the link's text nothing more than an address?
     */
    private static function isBare(DOMElement $link, string $target): bool
    {
        $text = trim(self::visibleText($link));

        return $text === '' || $text === $target || preg_match('~^https?://~i', $text) === 1;
    }

    /**
     * The text a reader would see, leaving out the original markup the parser
     * keeps alongside it.
     */
    private static function visibleText(DOMElement $element): string
    {
        $text = '';

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMText) {
                $text .= $child->nodeValue;
            } elseif ($child instanceof DOMElement && ! in_array($child->nodeName, ['s', 'e', 'i'], true)) {
                $text .= self::visibleText($child);
            }
        }

        return $text;
    }
}
