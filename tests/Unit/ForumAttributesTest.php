<?php

declare(strict_types=1);

namespace Ramon\GuestCta\Tests\Unit;

use Flarum\Api\Context;
use Flarum\Api\JsonApi;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Guest;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Ramon\GuestCta\Api\ForumAttributes;
use stdClass;

/**
 * Nothing this extension adds to the forum payload is for signed-in readers,
 * and the CTA text is whatever the admin typed — so who gets what is pinned
 * down here.
 */
#[Group('security')]
final class ForumAttributesTest extends TestCase
{
    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed> what the given reader is sent
     */
    private function payload(array $settings, User $actor): array
    {
        $repository = $this->createStub(SettingsRepositoryInterface::class);
        $repository->method('get')->willReturnCallback(fn (string $key, mixed $default = null) => $settings[$key] ?? $default);

        // A real context rather than a double: Context has a method() of its
        // own, which PHPUnit can no longer stand in for.
        $context = (new Context(new JsonApi(), RequestUtil::withActor(new ServerRequest(), $actor)))->withModel(new stdClass());

        $payload = [];

        foreach ((new ForumAttributes($repository))() as $field) {
            if ($field->isVisible($context)) {
                $payload[$field->name] = $field->getValue($context);
            }
        }

        return $payload;
    }

    public function test_a_signed_in_reader_is_sent_nothing(): void
    {
        $settings = [
            'guest-cta.hide_links' => '1',
            'guest-cta.show_post_cta' => '1',
            'guest-cta.position' => 'end',
            'guest-cta.cta_text' => 'Join us',
        ];

        self::assertSame([], $this->payload($settings, new User()));
    }

    public function test_a_guest_is_sent_only_the_switches_while_the_cta_is_off(): void
    {
        self::assertSame(
            ['guestCtaHideLinks' => false, 'guestCtaShowPostCta' => false],
            $this->payload(['guest-cta.position' => '3', 'guest-cta.cta_text' => 'Join us'], new Guest())
        );
    }

    public function test_a_guest_is_sent_the_position_and_no_text_when_none_was_written(): void
    {
        self::assertSame(
            ['guestCtaHideLinks' => true, 'guestCtaShowPostCta' => true, 'guestCtaPosition' => '3'],
            $this->payload(['guest-cta.hide_links' => '1', 'guest-cta.show_post_cta' => '1', 'guest-cta.position' => '3', 'guest-cta.cta_text' => "  \n "], new Guest())
        );
    }

    public function test_a_guest_is_sent_the_written_text_trimmed(): void
    {
        $payload = $this->payload(['guest-cta.show_post_cta' => '1', 'guest-cta.position' => 'end', 'guest-cta.cta_text' => "  Hallo!\nZeile zwei \n"], new Guest());

        self::assertSame('end', $payload['guestCtaPosition']);
        self::assertSame("Hallo!\nZeile zwei", $payload['guestCtaText']);
    }

    public function test_an_unknown_position_falls_back_to_the_first_post(): void
    {
        foreach (['0', '6', 'last', '', null] as $stored) {
            $payload = $this->payload(['guest-cta.show_post_cta' => '1', 'guest-cta.position' => $stored], new Guest());

            self::assertSame('1', $payload['guestCtaPosition']);
        }
    }
}
