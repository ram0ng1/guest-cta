<?php

declare(strict_types=1);

namespace Ramon\GuestCta\Formatter;

use Flarum\Foundation\Config;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Renderer;

/**
 * Hides external links in posts from guests, at render time.
 */
class GateGuestLinks
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected TranslatorInterface $translator,
        protected Config $config
    ) {
    }

    public function __invoke(Renderer $renderer, mixed $context, string $xml, ?ServerRequestInterface $request = null): string
    {
        // Cheapest checks first: this runs for every post on the page.
        if (! $context instanceof Post || ! LinkGate::hasLinks($xml) || ! $this->appliesTo($request)) {
            return $xml;
        }

        $renderer->setParameter(
            ConfigureGuestLinks::LABEL_PARAMETER,
            $this->translator->trans('ramon-guest-cta.forum.link_cta.placeholder')
        );

        return $this->gate($xml);
    }

    /**
     * Should links be hidden from whoever sent this request?
     *
     * Without a request there is no telling who is reading — a notification
     * email, say — and those go to members. The same holds for a request that
     * never went through the authentication middleware.
     *
     * Public, along with {@see gate()}, so that an extension which shows post
     * text without rendering it — a plain-text excerpt, a feed — can apply the
     * same rule to the XML it reads.
     */
    public function appliesTo(?ServerRequestInterface $request): bool
    {
        return $request !== null
            && $this->settings->get('guest-cta.hide_links')
            && $request->getAttribute('actorReference') !== null
            && RequestUtil::getActor($request)->isGuest();
    }

    /**
     * Take the external links out of a parsed post.
     */
    public function gate(string $xml): string
    {
        return LinkGate::gate($xml, $this->config->url()->getHost());
    }
}
