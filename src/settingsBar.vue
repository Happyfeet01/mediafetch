<template>
  <section id="ncdownloader-settings-collapsible-container">
    <div class="ncdownloader-settings-item" :data-tippy-content="errorTooltip">
      <toggleButton
        :disabledText="errorText"
        :enabledText="errorText"
        :defaultStatus="toggleStatus"
        @changed="toggle"
        name="ncd_hide_errors"
      ></toggleButton>
    </div>
    <div class="ncdownloader-settings-item" :data-tippy-content="btTooltip">
      <toggleButton
        v-if="isAdmin"
        disabledText="Disable BT for non-admin users"
        enabledText="Disable BT for non-admin users"
        :defaultStatus="btStatus"
        name="ncd_disable_bt"
        @changed="toggle"
      ></toggleButton>
    </div>
    <div class="ncdownloader-settings-item">
      <a :href="personal.url" title="">
        <button>{{ personal.title }}</button>
      </a>
    </div>
    <div class="ncdownloader-settings-item" v-if="isAdmin">
      <a :href="admin.url" :title="admin.title">
        <button>{{ admin.title }}</button>
      </a>
    </div>
  </section>
</template>

<script>
import toggleButton from "./components/toggleButton";
import helper from "./utils/helper";
import { translate as t } from "@nextcloud/l10n";

const APP_ID = "mediafetch";
const basePath = `/apps/${APP_ID}`;

export default {
  name: "settingsBar",
  inject: ["settings"],
  data() {
    const personal = {
      title: t(APP_ID, "Personal Settings"),
      url: this.settings.personal_url,
    };
    const admin = {
      title: t(APP_ID, "Admin Settings"),
      url: this.settings.admin_url,
    };
    return {
      personal,
      admin,
      isAdmin: this.settings.is_admin,
      sectionName: t(APP_ID, "Settings"),
      errorText: t(APP_ID, "Hide Errors"),
      toggleStatus: helper.str2Boolean(this.settings.ncd_hide_errors),
      btStatus: helper.str2Boolean(this.settings.ncd_disable_bt),
      errorTooltip: t(APP_ID, "Enable this to hide errors"),
      btTooltip: t(APP_ID, "Disable BT for non-admin users"),
    };
  },
  methods: {
    toggle(name, value) {
      const data = {};
      data[name] = value ? 1 : 0;
      const path = name === "ncd_disable_bt" ? "/admin/save" : "/personal/save";
      const url = helper.generateUrl(basePath + path);
      helper.httpClient(url)
        .setData(data)
        .setHandler((resp) => {
          if (resp["message"]) {
            helper.message(t(APP_ID, resp["message"]), 1000);
          }
        })
        .send();
    },
  },
  components: {
    toggleButton,
  },
};
</script>

<style lang="scss">
@use "css/variables.scss" as *;
#ncdownloader-settings-collapsible-container {
  display: flex;
  flex-flow: column wrap;
}
</style>
