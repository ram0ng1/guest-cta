// @ts-nocheck
import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import CommentPost from 'flarum/forum/components/CommentPost';

// PHP side uses boolval in serializeToForum, so values arrive as true/false/null.
const settingEnabled = (key, defaultValue = false) => {
  const val = app.forum?.attribute(key);
  if (val === null || val === undefined) return defaultValue;
  return !!val;
};

const isExternalLink = (link) => {
  try {
    const url = new URL(link.href, window.location.href);
    return url.hostname !== window.location.hostname;
  } catch (_) {
    return false;
  }
};

const openLogin = () =>
  flarum.reg.asyncModuleImport('flarum/forum/components/LogInModal').then((M) => app.modal.show(M));

const openSignUp = () =>
  flarum.reg.asyncModuleImport('flarum/forum/components/SignUpModal').then((M) => app.modal.show(M));

// Replace external links inside post bodies with a clickable lock placeholder.
// data-guestcta-gated prevents double-processing on onupdate redraws.
const gateGuestLinks = (component) => {
  if (app.session.user) return;
  if (!settingEnabled('guestCtaHideLinks', false)) return;
  const root = component.element;
  if (!root) return;
  const body = root.querySelector('.Post-body');
  if (!body) return;

  const label = app.translator.trans('ramon-guest-cta.forum.link_cta.placeholder', {}, true);

  body.querySelectorAll('a[href]:not([data-guestcta-gated])').forEach((link) => {
    if (!isExternalLink(link)) return;

    const placeholder = document.createElement('span');
    placeholder.className = 'GuestCtaLink';
    placeholder.setAttribute('data-guestcta-gated', '1');
    placeholder.setAttribute('role', 'button');
    placeholder.tabIndex = 0;

    const icon = document.createElement('i');
    icon.className = 'fas fa-lock';
    icon.setAttribute('aria-hidden', 'true');

    const text = document.createElement('span');
    text.className = 'GuestCtaLink-label';
    text.textContent = label;

    placeholder.appendChild(icon);
    placeholder.appendChild(text);

    placeholder.addEventListener('click', openLogin);
    placeholder.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') openLogin();
    });

    link.parentNode.replaceChild(placeholder, link);
  });
};

app.initializers.add('ramon-guest-cta', () => {
  // ── Guest link gating ──────────────────────────────────────────────────────
  // CommentPost oncreate/onupdate fire whenever a post is rendered/redrawn.
  extend(CommentPost.prototype, 'oncreate', function () {
    gateGuestLinks(this);
  });
  extend(CommentPost.prototype, 'onupdate', function () {
    gateGuestLinks(this);
  });

  // ── CTA between posts (guest-only, configurable position 1-5) ──────────────
  // PostStream is code-split — use the string-based extend so flarum.reg.onLoad
  // applies the patch when the chunk evaluates. Same pattern flarum/realtime uses.
  const getCtaPosition = () => {
    try {
      const pos = app.forum?.attribute('guestCtaPosition') ?? '1';
      const parsed = parseInt(pos, 10);
      return parsed >= 1 && parsed <= 5 ? parsed : 1;
    } catch (_) {
      return 1;
    }
  };

  const renderCta = () => (
    <div className="PostStream-item PostStream-guestCta">
      <div className="GuestCta-wrapper">
        <div className="GuestCta">
          <span className="GuestCta-text">
            {app.translator.trans('ramon-guest-cta.forum.post_cta.text')}
          </span>
          <span className="GuestCta-buttons">
            <Button
              className="Button Button--primary GuestCta-btn GuestCta-btn--login"
              icon="fas fa-sign-in-alt"
              onclick={openLogin}
            >
              {app.translator.trans('core.forum.header.log_in_link')}
            </Button>
            <span className="GuestCta-or">
              {app.translator.trans('ramon-guest-cta.forum.post_cta.or')}
            </span>
            <Button
              className="Button GuestCta-btn GuestCta-btn--signup"
              icon="fas fa-user-plus"
              onclick={openSignUp}
            >
              {app.translator.trans('core.forum.header.sign_up_link')}
            </Button>
          </span>
        </div>
      </div>
    </div>
  );

  const ctaEnabled = () => settingEnabled('guestCtaShowPostCta', false);

  // Position 1: afterFirstPostItems — Flarum-native hook, fully Mithril-managed.
  extend('flarum/forum/components/PostStream', 'afterFirstPostItems', function (items) {
    if (!ctaEnabled() || app.session.user) return;
    if (getCtaPosition() === 1) items.add('guest-cta', renderCta(), 100);
  });

  // Positions 2-5: extend view() and splice the CTA vnode after the N-th comment post.
  // Event posts (stickied, locked, renamed…) carry a different data-type and must
  // not advance the counter — the position setting refers to comment posts only.
  extend('flarum/forum/components/PostStream', 'view', function (rootVnode) {
    if (!ctaEnabled() || app.session.user) return;

    const pos = getCtaPosition();
    if (pos === 1) return;

    const children = rootVnode?.children;
    if (!Array.isArray(children)) return;

    let count = 0;
    for (let i = 0; i < children.length; i++) {
      const attrs = children[i]?.attrs;
      if (attrs?.['data-number'] && attrs?.['data-type'] === 'comment') {
        if (++count === pos) {
          children.splice(i + 1, 0, renderCta());
          break;
        }
      }
    }
  });
});
