<?php

namespace Ramon\GuestCta;

use Flarum\Api\Resource\ForumResource;
use Flarum\Extend;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Settings())
        ->default('guest-cta.show_post_cta', false)
        ->default('guest-cta.position', '1')
        ->default('guest-cta.cta_text', '')
        ->default('guest-cta.hide_links', false),

    // Sent to guests only. See ForumAttributes.
    (new Extend\ApiResource(ForumResource::class))
        ->fields(Api\ForumAttributes::class),

    // Links are taken out of the post on the server, so a guest never
    // receives the address at all.
    (new Extend\Formatter())
        ->configure(Formatter\ConfigureGuestLinks::class)
        ->render(Formatter\GateGuestLinks::class),
];
