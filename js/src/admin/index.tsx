// @ts-nocheck
import app from 'flarum/admin/app';

const isOn = (value) => value === '1' || value === 1 || value === true || value === 'true';

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

    // Position depends on the show_post_cta toggle. Callback runs on every render,
    // so flipping the switch above immediately hides/shows this setting.
    .registerSetting(function () {
      if (!isOn(this.setting('guest-cta.show_post_cta')())) return null;

      return this.buildSettingComponent({
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
        },
        default: '1',
      });
    }, 0, 'guest-cta.position');
});
