<template>
  <button
    type="button"
    @click="handler"
    class="downloader-folder-settings"
    data-tippy-content="Set Download Folder"
    :data-path="path"
    :title="title"
    :aria-label="title"
  >
    {{ buttonLabel }}
  </button>
</template>
<script>
import { FilePickerClosed, getFilePickerBuilder } from "@nextcloud/dialogs";
import "@nextcloud/dialogs/style.css";
import { translate as t } from "@nextcloud/l10n";
import helper from "../utils/helper";

const APP_ID = "mediafetch";

export default {
  name: "folderSettings",
  computed: {
    title() {
      return t(APP_ID, "Set Download Folder");
    },
    buttonLabel() {
      return t(APP_ID, "Choose folder");
    },
  },
  methods: {
    async savePath(element, path) {
      const currentPath = element.getAttribute("data-path") || "/";
      if (currentPath === path) {
        helper.info(t(APP_ID, "This folder is already selected"));
        return;
      }

      const url = helper.generateUrl("/apps/mediafetch/personal/save");
      helper
        .httpClient(url)
        .setData({ ncd_downloader_dir: path })
        .setHandler((data) => {
          if (data.status) {
            element.setAttribute("data-path", path);
            helper.info(t(APP_ID, "Download folder updated to {path}", { path }));
          } else if (data.error) {
            helper.error(data.error);
          }
        })
        .send();
    },
    handler(event) {
      const element = event.currentTarget;
      const currentPath = element.getAttribute("data-path") || "/";

      const picker = getFilePickerBuilder(t(APP_ID, "Choose download folder"))
        .allowDirectories(true)
        .setMimeTypeFilter([])
        .setMultiSelect(false)
        .startAt(currentPath)
        .setButtonFactory((_selection, path) => [
          {
            label: t(APP_ID, "Use this folder"),
            variant: "primary",
            callback: async () => {
              await this.savePath(element, path);
            },
          },
        ])
        .build();

      picker.pick().catch((error) => {
        if (!(error instanceof FilePickerClosed)) {
          helper.error(error?.message || t(APP_ID, "Could not open folder picker"));
        }
      });
    },
  },
  props: ["path"],
};
</script>
<style scoped lang="scss">
@use "../css/variables.scss" as *;

.downloader-folder-settings {
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  min-width: 130px;
  min-height: 42px;
  height: 100% !important;
  padding: 0 12px !important;
  border: 1px solid var(--color-border, #5a5a5a) !important;
  border-radius: var(--border-radius-element, 4px);
  background-color: var(--color-main-background, #fff) !important;
  color: var(--color-main-text, #222) !important;
  cursor: pointer;
  white-space: nowrap;
  font-weight: 600;
  font-size: 14px;

  &:hover,
  &:focus {
    background-color: var(--color-background-hover, #eef4f8) !important;
  }
}

@media only screen and (max-width: 768px) {
  .downloader-folder-settings {
    min-width: 100%;
    padding: 0 10px !important;
  }
}
</style>
