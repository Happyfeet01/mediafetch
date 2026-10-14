<template>
  <div :id="name">
    <label :for="name">{{ name }}</label>
    <input
      type="text"
      :id="name"
      :name="name"
      :value="value"
      :placeholder="tr('Leave empty if no value needed')"
      class="form-input-text"
    />
    <button class="has-content icon-close" @click="remove"></button>
  </div>
</template>
<script>
import { translate as t } from "@nextcloud/l10n";
export default {
  name: "editableRow",
  props: {
    name: String,
    value: String,
    placeholder: String,
  },
  data() {
    return {};
  },
  methods: {
    tr(message) { return t("mediafetch", message); },
    remove(e) {
      let ele = e.target;
      let container = ele.closest("div");
      container.remove();
    },
  },
};
</script>
