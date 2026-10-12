<?php

declare(strict_types=1);

namespace Ramon\GuestCta\Formatter;

use s9e\TextFormatter\Configurator;

/**
 * Registers the placeholder a guest sees where a link was.
 *
 * It is a tag of its own rather than a variation of the URL template: themes
 * and other extensions replace that template, and a gate that depended on it
 * would stop hiding links the moment one did. No parser plugin produces this
 * tag, so it can only ever come from {@see LinkGate}.
 */
class ConfigureGuestLinks
{
    public const LABEL_PARAMETER = 'L_GUESTCTA_LINK';

    public function __invoke(Configurator $config): void
    {
        // The label depends on the reader's language, so it is passed in when
        // the post is rendered. See GateGuestLinks.
        $config->rendering->parameters[self::LABEL_PARAMETER] = '';

        $config->tags->add(LinkGate::TAG)->template =
            '<span class="GuestCtaLink" role="button" tabindex="0">'
                .'<i class="fas fa-lock" aria-hidden="true"></i>'
                .'<span class="GuestCtaLink-label"><xsl:value-of select="$'.self::LABEL_PARAMETER.'"/></span>'
            .'</span>';
    }
}
