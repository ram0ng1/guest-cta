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
- **Join CTA between posts** — Insert a Login / Sign Up card after a configurable post position (#1 to #5) inside any discussion. Visible only to guests.

## Requirements

- Flarum `^2.0.0`

## Installation

```sh
composer require ramon/guest-cta
```

## Configuration

All settings are available in the admin panel under the Guest CTA extension:

| Setting | Description | Default |
|---|---|---|
| Hide links for guests | Replace external links in posts with a Login prompt | `false` |
| Show Join CTA after first post | Display a Login / Sign Up card after the configured post | `false` |
| CTA position | Insert the CTA after post #1 to #5 (only when CTA is enabled) | `1` |

## Author

- [Ramon Guilherme](https://ramonguilherme.com.br)

## License

MIT
