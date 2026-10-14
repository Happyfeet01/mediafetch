<template>
  <div class="section ncdownloader-general-settings">
    <h3>{{ tr('General Settings') }}</h3>
    <settingsRow v-for="(option, key) in optionRows" v-bind:key="key" :value="option.value" :id="option.id"
      :label="tr(option.label)" :placeholder="option.placeholder" :path="option.path" :useBtn="true" />
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
      this.options = options;
    } catch (e) {
      helper.error(e);
    }
  },
};
</script>
