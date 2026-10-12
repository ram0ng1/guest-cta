<?php

declare(strict_types=1);

namespace Ramon\GuestCta\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema\Attribute;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * What the forum frontend needs to know to show the CTA.
 *
 * All of it is for guests, so none of it is sent to anyone signed in — that
 * includes the admin's own CTA text, which can run to a paragraph.
 */
class ForumAttributes
{
    public const POSITIONS = ['1', '2', '3', '4', '5', 'end'];

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function __invoke(): array
    {
        $guest = fn (object $forum, Context $context): bool => $context->getActor()->isGuest();
        $guestWithCta = fn (object $forum, Context $context): bool => $context->getActor()->isGuest() && $this->showsCta();

        return [
            Attribute::make('guestCtaHideLinks')
                ->visible($guest)
                ->get(fn () => (bool) $this->settings->get('guest-cta.hide_links')),

            Attribute::make('guestCtaShowPostCta')
                ->visible($guest)
                ->get(fn () => $this->showsCta()),

            Attribute::make('guestCtaPosition')
                ->visible($guestWithCta)
                ->get(fn () => $this->position()),

            Attribute::make('guestCtaText')
                ->visible(fn (object $forum, Context $context): bool => $guestWithCta($forum, $context) && $this->text() !== '')
                ->get(fn () => $this->text()),
        ];
    }

    private function showsCta(): bool
    {
        return (bool) $this->settings->get('guest-cta.show_post_cta');
    }

    private function position(): string
    {
        $position = (string) $this->settings->get('guest-cta.position');

        return in_array($position, self::POSITIONS, true) ? $position : '1';
    }

    private function text(): string
    {
        return trim((string) $this->settings->get('guest-cta.cta_text'));
    }
}
