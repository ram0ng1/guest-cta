import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import type ItemList from 'flarum/common/utils/ItemList';
import type PostStream from 'flarum/forum/components/PostStream';
import { openLogIn, openSignUp } from './authModals';

type Position = '1' | '2' | '3' | '4' | '5' | 'end';

// Absent unless the CTA is switched on. See ForumAttributes.php.
const position = () => app.forum.attribute<Position | undefined>('guestCtaPosition');

/**
 * @param key Needed wherever the CTA sits among the stream's own items, which
 *   are keyed. Left out inside `afterFirstPostItems`, whose items are not.
 */
const cta = (key?: string) => {
  // A forum that has closed registration has nothing to offer but logging in.
  const signUp = app.forum.attribute<boolean>('allowSignUp');

  return (
    <div className="PostStream-item PostStream-guestCta" key={key}>
      <div className="GuestCta-wrapper">
        <div className="GuestCta">
          <span className="GuestCta-text">
            {app.forum.attribute<string | undefined>('guestCtaText') || app.translator.trans('ramon-guest-cta.forum.post_cta.text')}
          </span>
          <span className="GuestCta-buttons">
            <Button className="Button Button--primary GuestCta-btn GuestCta-btn--login" icon="fas fa-sign-in-alt" onclick={openLogIn}>
              {app.translator.trans('core.forum.header.log_in_link')}
            </Button>
            {signUp && <span className="GuestCta-or">{app.translator.trans('ramon-guest-cta.forum.post_cta.or')}</span>}
            {signUp && (
              <Button className="Button GuestCta-btn GuestCta-btn--signup" icon="fas fa-user-plus" onclick={openSignUp}>
                {app.translator.trans('core.forum.header.sign_up_link')}
              </Button>
            )}
          </span>
        </div>
      </div>
    </div>
  );
};

/**
 * Where in the stream's items the CTA goes, or -1 if it does not belong in
 * what is on screen right now.
 */
function indexFor(stream: PostStream['stream'], items: (Mithril.Vnode<any> | null)[], position: Position): number {
  if (position === 'end') {
    if (!stream.viewingEnd()) return -1;

    // Ahead of the reply placeholder, which closes the stream.
    const reply = items.findIndex((item) => item?.key === 'reply');

    return reply === -1 ? items.length : reply;
  }

  // "After post #3" means the discussion's third post, not the third one that
  // happens to be loaded for a reader who landed in the middle.
  if (stream.visibleStart !== 0) return -1;

  // Event posts (stickied, locked, renamed…) carry a different data-type and
  // must not advance the counter — the setting refers to comment posts only.
  let seen = 0;
  const target = Number(position);
  const post = items.findIndex((item) => item?.attrs?.['data-type'] === 'comment' && ++seen === target);

  return post === -1 ? -1 : post + 1;
}

export default function addPostStreamCta() {
  // PostStream is code-split — use the string-based extend so flarum.reg.onLoad
  // applies the patch when the chunk evaluates. Same pattern flarum/realtime uses.

  // Position 1: afterFirstPostItems — Flarum-native hook, fully Mithril-managed.
  extend('flarum/forum/components/PostStream', 'afterFirstPostItems', function (items: ItemList<Mithril.Children>) {
    if (position() === '1') items.add('guest-cta', cta(), 100);
  });

  // Everywhere else: splice the CTA in among the rendered items.
  extend('flarum/forum/components/PostStream', 'view', function (this: PostStream, vnode: Mithril.Vnode<any>) {
    const where = position();
    if (!where || where === '1' || !Array.isArray(vnode.children)) return;

    const items = vnode.children as (Mithril.Vnode<any> | null)[];
    const index = indexFor(this.stream, items, where);

    if (index !== -1) items.splice(index, 0, cta('guest-cta'));
  });
}
