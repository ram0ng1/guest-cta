import app from 'flarum/forum/app';

// Both modals are lazy chunks in core. Handing the import to the modal manager
// — rather than awaiting it here — shows its loading state while the chunk
// arrives, the same way the header buttons do.
export const openLogIn = () => app.modal.show(() => import('flarum/forum/components/LogInModal'));

export const openSignUp = () => app.modal.show(() => import('flarum/forum/components/SignUpModal'));
