<template>
  <div class="section" :class="[classes]" :id="container">
    <h3 class="title">{{ title }}</h3>
    <div class="mediafetch-options-editor" :id="id" :path="path">
      <editableRow
        v-for="(option, key) in rows"
        v-bind:key="key"
        :value="option.value"
        :name="option.name"
        :placeholder="option.placeholder"
      />
      <button
        class="custom-settings-add-btn"
        @click.prevent="newOption($event, name)"
        :data-tippy-content="tr('Add new options')"
      >
        <slot name="add">{{ tr('New Option') }}</slot>
      </button>
      <button
        class="custom-settings-save-btn"
        @click.prevent="saveOptions"
        :data-rel="id"
      >
        <slot name="save">{{ tr('Save') }}</slot>
      </button>
    </div>
  </div>
</template>
<script>
import helper from "../utils/helper";
import settingsForm from "../lib/settingsForm";
import editableRow from "./editableRow";

export default {
  name: "customOptions",
  props: {
    path: String,
    name: {
      type: String,
      default: "settings",
    },
    title: {
      type: String,
      default: "Custom Settings",
    },
    classes: String,
    validOptions: Array,
    options: Array,
    hiddenOptions: {
      type: Array,
      default: () => [],
    },
    mergeReadPath: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      id: "custom-" + this.name,
      classes: "custom-settings-container",
      container: "custom-settings-container",
      validOptions: this.validOptions,
      options: [],
    };
  },
  components: {
    editableRow,
  },
  computed: {
    rows() {
      return this.options.filter((option) => !this.hiddenOptions.includes(option.name));
    },
    allowedOptions() {
      return this.validOptions.filter((option) => !this.hiddenOptions.includes(option));
    },
  },
  methods: {
    tr(message) { return helper.t(message); },
    newOption(e, baseName) {
      e.stopPropagation();
      let element = e.target;
      let nodeList, key, value;
      nodeList = document.querySelectorAll(`[id^='${baseName}-key']`);
      if (nodeList.length === 0) {
        key = `${baseName}-key-1`;
        value = `${baseName}-value-1`;
      } else {
        let index = nodeList.length + 1;
        key = `${baseName}-key-${index}`;
        value = `${baseName}-value-${index}`;
        //selector = `[id^='${baseName}-key']`;
      }
      let form = settingsForm.getInstance();
      element.before(form.createInputGroup(key, value));
      helper.autoComplete(`[id^='${baseName}-key']`, this.allowedOptions);
    },
    saveOptions(e) {
      let element = e.target;
      let container = element.getAttribute("data-rel");
      let data = helper.getData(container);
      let url = helper.generateUrl(data._path);
      data = helper.transformParams(data, this.name);
      let badOptions = [];
      for (let name in data) {
        if (!this.allowedOptions.includes(name)) {
          badOptions.push(name);
        }
      }
      if (badOptions.length > 0) {
        helper.error(this.tr('Invalid options: {options}').replace('{options}', badOptions.join(',')));
        return;
      }
      const submit = (payload) => {
        helper
          .httpClient(url)
          .setData(payload)
          .setHandler((resp) => {
            if (!resp || resp.error) {
              helper.error(resp?.error || this.tr('Could not save settings.'));
              return;
            }
            this.options = Object.keys(payload).map((key) => ({ name: key, value: payload[key] }));
            const inputDiv = element.parentElement.querySelectorAll(
              `div[id^='${this.name}-key']`
            );
            inputDiv.forEach((input) => input.remove());
            helper.info(helper.t(resp.message));
            this.$emit('saved');
          })
          .setErrorHandler(() => helper.error(this.tr('Could not save settings.')))
          .send();
      };

      if (!this.mergeReadPath) {
        submit(data);
        return;
      }

      // Advanced yt-dlp settings only show options absent from the graphical
      // editor. Read the latest stored values to preserve hidden GUI options
      // while allowing users to remove old advanced-only options.
      helper
        .httpClient(helper.generateUrl(this.mergeReadPath))
        .setHandler((stored) => {
          if (!stored || typeof stored !== 'object' || Array.isArray(stored)) {
            helper.error(this.tr('Could not load current settings.'));
            return;
          }
          const preserved = {};
          for (const key in stored) {
            if (this.hiddenOptions.includes(key)) {
              preserved[key] = stored[key];
            }
          }
          submit({ ...preserved, ...data });
        })
        .setErrorHandler(() => helper.error(this.tr('Could not load current settings.')))
        .send();
    },
  },
  mounted() {
    this.$emit("mounted", event, this);
  },
};
</script>
<style scoped lang="scss"></style>
