import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import CommentPost from 'flarum/forum/components/CommentPost';
import { gateRemainingLinks, listenForPlaceholders } from './gateLinks';
import addPostStreamCta from './postStreamCta';

app.initializers.add('ramon-guest-cta', () => {
  // Everything here is for guests, so a signed-in reader gets none of it.
  // `app.session` is not built until after the initializers have run, hence
  // the payload. Logging in or out reloads the page, so this cannot go stale.
  if (app.data.session.userId) return;

  // CommentPost oncreate/onupdate fire whenever a post is rendered/redrawn.
  extend(CommentPost.prototype, ['oncreate', 'onupdate'], function () {
    gateRemainingLinks(this);
  });

  listenForPlaceholders();
  addPostStreamCta();
});
