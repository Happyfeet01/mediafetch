<template>
  <form class="upload-file-form" id="nc-vue-upload-file-form" :action="path">
    <div class="icon-upload fileinput">
      <input type="file" name="torrentfile" id="torrentfile" accept=".torrent" :title="tr('Upload torrent file')" :aria-label="tr('Upload torrent file')" @change="handler" />
    </div>
  </form>
</template>
<script>
import { translate as t } from "@nextcloud/l10n";
export default {
  name: "uploadFile",
  methods: {
    tr(message) { return t('mediafetch', message); },
    handler(event) {
      this.$emit("uploadfile", event, this);
    },
  },
  props: ["path"],
};
</script>
<style scoped lang="scss">
@use "../css/variables.scss" as *;
#nc-vue-upload-file-form {
  .icon-upload {
    background-image: url("../../img/upload.svg");
  }
  .icon-upload.fileinput {
    background-color: #9f9fad;
    height: 100%;
    & input[type="file"] {
      cursor: pointer;
      font-size: 16px;
      opacity: 0;
      position: relative;
      width: $upload-width;
      z-index: 20;
    }
  }
}
</style>
