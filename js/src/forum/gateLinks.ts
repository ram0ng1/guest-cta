import app from 'flarum/forum/app';
import type CommentPost from 'flarum/forum/components/CommentPost';
import { openLogIn } from './authModals';

const PLACEHOLDER = '.GuestCtaLink';

const isExternalLink = (link: HTMLAnchorElement) => {
  try {
    return new URL(link.href, window.location.href).hostname !== window.location.hostname;
  } catch (_) {
    return false;
  }
};

// A link is "raw" when its visible text is just the URL itself (or empty).
// In that case there's no descriptive context to preserve, so we replace the
// whole link with the placeholder — otherwise we'd leave an ugly bare URL.
const isRawUrlLink = (link: HTMLAnchorElement) => {
  const text = (link.textContent || '').trim();

  return !text || text === link.href || /^https?:\/\//i.test(text);
};

// Same markup the server renders. See ConfigureGuestLinks.php.
const buildPlaceholder = (label: string) => {
  const placeholder = document.createElement('span');
  placeholder.className = 'GuestCtaLink';
  placeholder.setAttribute('role', 'button');
  placeholder.tabIndex = 0;

  const icon = document.createElement('i');
  icon.className = 'fas fa-lock';
  icon.setAttribute('aria-hidden', 'true');

  const text = document.createElement('span');
  text.className = 'GuestCtaLink-label';
  text.textContent = label;

  placeholder.append(icon, text);

  return placeholder;
};

/**
 * Hide whatever external links are still in a rendered post.
 *
 * The server already takes ordinary links out before a guest receives the
 * post, so this only finds something when another extension renders a link
 * through a tag of its own.
 */
export function gateRemainingLinks(post: CommentPost) {
  if (!app.forum.attribute<boolean>('guestCtaHideLinks')) return;

  const links = post.element?.querySelectorAll<HTMLAnchorElement>('.Post-body a[href]');
  if (!links?.length) return;

  let label: string | undefined;

  links.forEach((link) => {
    const parent = link.parentNode;
    if (!parent || !isExternalLink(link)) return;

    label ??= app.translator.trans('ramon-guest-cta.forum.link_cta.placeholder', {}, true);
    const placeholder = buildPlaceholder(label);

    if (isRawUrlLink(link)) {
      link.replaceWith(placeholder);
      return;
    }

    // Preserve the original link's visible text/markup so the surrounding
    // sentence still makes sense, then append " (placeholder)" right after.
    link.replaceWith(...Array.from(link.childNodes), ' (', placeholder, ')');
  });
}

/**
 * Open the log in modal from any placeholder, wherever it came from.
 *
 * Delegated from the document because the placeholders arrive inside post
 * HTML, which is replaced wholesale whenever a post re-renders.
 */
export function listenForPlaceholders() {
  const placeholderOf = (e: Event) => (e.target instanceof Element ? e.target.closest(PLACEHOLDER) : null);

  document.addEventListener('click', (e) => {
    if (placeholderOf(e)) openLogIn();
  });

  document.addEventListener('keydown', (e) => {
    if ((e.key !== 'Enter' && e.key !== ' ') || !placeholderOf(e)) return;

    // Space would otherwise scroll the page behind the modal.
    e.preventDefault();
    openLogIn();
  });
}
