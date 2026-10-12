<p align="center">
  <img src="icon.svg" width="80" alt="Guest CTA">
  <h1 align="center">Guest CTA</h1>
</p>

<p align="center">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square">
  <a href="https://packagist.org/packages/ramon/guest-cta">
    <img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/ramon/guest-cta.svg?style=flat-square">
  </a>
  <a href="https://packagist.org/packages/ramon/guest-cta">
    <img alt="Total Downloads" src="https://img.shields.io/packagist/dt/ramon/guest-cta.svg?style=flat-square">
  </a>
</p>

<p align="center">
  Convert visitors into members on your <a href="https://flarum.org">Flarum</a> forum: hide external links from guests behind a login prompt and inject a Login / Sign Up CTA card between posts.
</p>

---

## Features

- **Hide links for guests** — Replace external links inside post bodies with a lock-icon pill that opens the Login modal when clicked. Internal links (mentions, discussion links) are left untouched.
- **Join CTA between posts** — Insert a Login / Sign Up card after a configurable post position (#1 to #5), or at the end of the discussion. Visible only to guests.
- **Your own CTA text** — Write the message in the admin panel instead of overriding the translation.

## Requirements

- Flarum `^2.0.0`

## Installation

```sh
composer require ramon/guest-cta
```

## Updating

```sh
composer update ramon/guest-cta
php flarum cache:clear
```

Clearing the cache matters here: the link placeholder is part of Flarum's compiled formatter, and that is only rebuilt when the cache is cleared.

## Configuration

All settings are available in the admin panel under the Guest CTA extension:

| Setting | Description | Default |
|---|---|---|
| Hide links for guests | Replace external links in posts with a Login prompt | `false` |
| Show Join CTA after a certain number of posts | Display a Login / Sign Up card in discussions | `false` |
| CTA position | After post #1 to #5, or at the end of the discussion (only when CTA is enabled) | `1` |
| CTA text | Your own message, as plain text; line breaks are kept. Leave empty to use the translated default (only when CTA is enabled) | empty |

"After post #3" means the third post of the discussion. A guest who lands in the middle of a long discussion sees the CTA once they scroll back to the start — pick "at the end of the discussion" to reach them where they are.

The Sign Up button is left out on forums where registration is closed.

## How links are hidden

Links are removed on the server, before a post is sent to a guest: the address is not in the page, not in the API response, and not in the markup served to browsers without JavaScript. Signed-in members get the post untouched.

This covers links and email addresses written in a post, whether pasted, Markdown or BBCode. A link that another extension renders through a tag of its own is only hidden in the browser, so its address is still present in the page source.

It also only covers posts as Flarum renders them. An extension that builds its own text from a post — a plain-text excerpt, a feed — has to apply the same rule itself:

```php
$gate = resolve(\Ramon\GuestCta\Formatter\GateGuestLinks::class);

if ($gate->appliesTo($request)) {
    $xml = $gate->gate($xml); // the post's parsed content
}
```

## Author

- [Ramon Guilherme](https://ramonguilherme.com.br)

## License

MIT
