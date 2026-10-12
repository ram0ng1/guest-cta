import app from 'flarum/admin/app';
import type ExtensionPage from 'flarum/admin/components/ExtensionPage';

const isOn = (value: unknown) => value === '1' || value === 1 || value === true || value === 'true';

app.initializers.add('ramon-guest-cta', () => {
  app.registry
    .for('ramon-guest-cta')

    .registerSetting({
      setting: 'guest-cta.hide_links',
      label: app.translator.trans('ramon-guest-cta.admin.settings.hide_links_label'),
      help: app.translator.trans('ramon-guest-cta.admin.settings.hide_links_help'),
      type: 'boolean',
    })

    .registerSetting({
      setting: 'guest-cta.show_post_cta',
      label: app.translator.trans('ramon-guest-cta.admin.settings.show_post_cta_label'),
      help: app.translator.trans('ramon-guest-cta.admin.settings.show_post_cta_help'),
      type: 'boolean',
    })

    // These depend on the show_post_cta toggle. The callback runs on every
    // render, so flipping the switch above immediately hides/shows them.
    .registerSetting(
      function (this: ExtensionPage) {
        if (!isOn(this.setting('guest-cta.show_post_cta')())) return null;

        return [
          this.buildSettingComponent({
            setting: 'guest-cta.position',
            label: app.translator.trans('ramon-guest-cta.admin.settings.position_label'),
            help: app.translator.trans('ramon-guest-cta.admin.settings.position_help'),
            type: 'select',
            options: {
              '1': app.translator.trans('ramon-guest-cta.admin.settings.position_1'),
              '2': app.translator.trans('ramon-guest-cta.admin.settings.position_2'),
              '3': app.translator.trans('ramon-guest-cta.admin.settings.position_3'),
              '4': app.translator.trans('ramon-guest-cta.admin.settings.position_4'),
              '5': app.translator.trans('ramon-guest-cta.admin.settings.position_5'),
              end: app.translator.trans('ramon-guest-cta.admin.settings.position_end'),
            },
            default: '1',
          }),

          this.buildSettingComponent({
            setting: 'guest-cta.cta_text',
            label: app.translator.trans('ramon-guest-cta.admin.settings.cta_text_label'),
            help: app.translator.trans('ramon-guest-cta.admin.settings.cta_text_help'),
            type: 'textarea',
            // The default message is a forum string, which the admin bundle
            // does not carry; the locale file points this key at it.
            placeholder: app.translator.trans('ramon-guest-cta.admin.settings.cta_text_placeholder', {}, true),
            rows: 3,
          }),
        ];
      },
      0,
      'guest-cta.position'
    );
});
