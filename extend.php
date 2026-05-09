<?php

namespace Ramon\GuestCta;

use Flarum\Extend;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Settings())
        ->serializeToForum('guestCtaShowPostCta', 'guest-cta.show_post_cta', 'boolval')
        ->serializeToForum('guestCtaPosition', 'guest-cta.position')
        ->serializeToForum('guestCtaHideLinks', 'guest-cta.hide_links', 'boolval')
        ->default('guest-cta.show_post_cta', false)
        ->default('guest-cta.position', '1')
        ->default('guest-cta.hide_links', false),
];
