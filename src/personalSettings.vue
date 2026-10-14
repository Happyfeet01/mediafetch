<template>
  <div class="section ncdownloader-general-settings">
    <h3>{{ tr('General Settings') }}</h3>
    <settingsRow v-for="(option, key) in optionRows" v-bind:key="key" :value="option.value" :id="option.id"
      :label="tr(option.label)" :placeholder="option.placeholder" :path="option.path" :useBtn="true" />
  </div>
  <div class="section mediafetch-seeding-section">
    <h3>{{ tr('BitTorrent seeding') }}</h3>
    <label class="mediafetch-seeding-option">
      <input type="checkbox" :checked="stopSeeding" :disabled="savingSeeding" @change="toggleSeeding($event)" />
      <span>{{ tr('Stop seeding after the download completes') }}</span>
    </label>
    <p>{{ tr('When enabled, aria2 stops seeding completed torrents (seed-time=0). Pieces may still be uploaded while downloading. This does not change existing torrents.') }}</p>
  </div>
  <customOptions v-if="!disallowAria2Settings || isAdmin" name="custom-aria2-settings"  :title="tr('Personal Aria2 Settings')"
    @mounted="renderAria2" path="/apps/mediafetch/personal/aria2/save" :validOptions="aria2Options">
    <template #save>{{ tr('Save Aria2 Settings') }}</template>
  </customOptions>
  <ytdlOptionsWizard ref="ytdlWizard" />
  <details class="mediafetch-advanced-settings">
    <summary class="mediafetch-advanced-summary">{{ tr('Advanced yt-dlp Settings') }}</summary>
    <p class="mediafetch-advanced-note">{{ tr('Edit additional yt-dlp options only when needed. These are the same saved settings as in the graphical assistant.') }}</p>
    <customOptions name="custom-ytdl-settings" :title="tr('Additional yt-dlp parameters')" @mounted="renderYtdl"
      @saved="refreshYtdlWizard"
      path="/apps/mediafetch/personal/ytdl/save" :validOptions="ytdlOptions"
      :hiddenOptions="managedYtdlOptions" mergeReadPath="/apps/mediafetch/personal/ytdl/get">
      <template #save>{{ tr('Save yt-dlp Settings') }}</template>
    </customOptions>
  </details>
</template>
<script>
import customOptions from "./components/customOptions";
import helper from "./utils/helper";
import safeAria2Options from "./utils/safeAria2Options";
import safeYtdlOptions from "./utils/safeYtdlOptions";
import managedYtdlOptions from "./utils/managedYtdlOptions";
import settingsRow from "./components/settingsRow";
import ytdlOptionsWizard from "./components/ytdlOptionsWizard";

export default {
  name: "personalSettings",
  data() {
    return {
      options: [],
      aria2Options: safeAria2Options,
      ytdlOptions: safeYtdlOptions,
      managedYtdlOptions,
      disallowAria2Settings: false,
      stopSeeding: false,
      savingSeeding: false,
      isAdmin: false,
    };
  },
  components: {
    customOptions,
    settingsRow,
    ytdlOptionsWizard,
  },
  methods: {
    tr(message) { return helper.t(message); },
    toggleSeeding(event) {
      const enabled = Boolean(event.target.checked);
      this.savingSeeding = true;
      helper.httpClient(helper.generateUrl('/apps/mediafetch/personal/save'))
        .setData({ ncd_stop_seeding: enabled ? '1' : '0' })
        .setHandler((result) => {
          this.savingSeeding = false;
          if (result && result.status && !result.error) {
            this.stopSeeding = enabled;
            helper.info(this.tr('Torrent seeding preference saved.'));
          } else {
            helper.error(result?.error || this.tr('Could not save torrent seeding preference.'));
            event.target.checked = this.stopSeeding;
          }
        })
        .setErrorHandler(() => {
          this.savingSeeding = false;
          event.target.checked = this.stopSeeding;
          helper.error(this.tr('Could not save torrent seeding preference.'));
        }).send();
    },
    refreshYtdlWizard() {
      if (this.$refs.ytdlWizard) this.$refs.ytdlWizard.reload();
    },
    renderAria2(event, $vm) {
      helper
        .httpClient(helper.generateUrl("/apps/mediafetch/personal/aria2/get"))
        .setHandler((data) => {
          if (!data) {
            return;
          }
          let input = [];
          for (let key in data) {
            if (safeAria2Options.includes(key))
              input.push({ name: key, value: data[key], id: key });
          }
          $vm.options = input;
        })
        .send();
    },
    renderYtdl(event, $vm) {
      helper
        .httpClient(helper.generateUrl("/apps/mediafetch/personal/ytdl/get"))
        .setHandler((data) => {
          if (!data) {
            return;
          }
          let input = [];
          for (let key in data) {
            if (safeYtdlOptions.includes(key))
              input.push({ name: key, value: data[key], id: key });
          }
          $vm.options = input;
        })
        .send();
    },
  },
  computed: {
    optionRows() {
      return this.options;
    },
  },
  mounted() {
    try {
      let data = this.$el.parentElement.getAttribute("data-settings");
      data = JSON.parse(data);
      let options = this.$el.parentElement.getAttribute("data-options");
      options = JSON.parse(options);
      this.disallowAria2Settings = helper.str2Boolean(data["disallow_aria2_settings"]);
      this.isAdmin = data["is_admin"];
      this.stopSeeding = helper.str2Boolean(data["ncd_stop_seeding"]);
      this.options = options;
    } catch (e) {
      helper.error(e);
    }
  },
};
</script>


<style scoped lang="scss">
.mediafetch-seeding-section {
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
  .mediafetch-seeding-option {
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 100%;
    font-size: 15px;
    line-height: 1.4;
    cursor: pointer;
    input {
      flex: 0 0 auto;
      min-width: 18px;
      min-height: 18px;
      margin: 0;
    }
    span { overflow-wrap: anywhere; }
  }
  p {
    color: var(--color-text-maxcontrast);
    line-height: 1.5;
    margin-top: 8px;
  }
}
</style>
