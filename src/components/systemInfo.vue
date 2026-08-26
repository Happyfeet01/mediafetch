<template>
    <div class="system-info-wrapper section">
        <h2 class="section-title">System Info</h2>
        <div class="system-info">
            <div class="system-info-item">
                <div class="system-info-item-label">Aria2 Version: </div>
                <div class="system-info-item-value"><span class="version">{{ aria2Ver }}</span></div>
            </div>
            <div class="system-info-item">
                <div class="system-info-item-label">yt-dlp Version: </div>
                <div class="system-info-item-value"><span class="version">{{ ytdVer }}</span>
                    <actionButton action="check" btnType="ytd" @clicked="checkUpdate" enableLoading="true"
                        className="check-button">{{ ytdBtn }}</actionButton>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import helper from "../utils/helper";
import actionButton from "./actionButton";

const YTD_CHECK_URL = "/apps/mediafetch/ytdl/release/check";
const YTD_UPDATE_URL = "/apps/mediafetch/ytdl/release/update";

export default {
    name: "systemInfo",
    data() {
        return {
            ytdBtn: "Check for Update",
        };
    },
    components: {
        actionButton
    },
    computed: {
        aria2Ver() {
            return this.$props.aria2Version;
        },
        ytdVer: {
            get() {
                return this.$props.ytdVersion;
            },
            set(value) {
                this.$emit("update:ytdVersion", value);
            }
        }
    },
    methods: {
        checkUpdate(event, $vm) {
            const { action } = $vm.$props;
            const path = action === "check" ? YTD_CHECK_URL : YTD_UPDATE_URL;
            helper
                .httpClient(helper.generateUrl(path))
                .setMethod("GET")
                .setHandler((data) => {
                    $vm.loading = false;
                    if (data.status) {
                        helper.info(data.message);
                        if (action === "check") {
                            this.ytdBtn = "Update";
                            $vm.$props.action = "update";
                        } else {
                            this.ytdBtn = "Check for Update";
                            $vm.$props.action = "check";
                            if (data.data) {
                                this.ytdVer = data.data;
                            }
                        }
                    } else {
                        helper.info(data.message);
                    }
                })
                .send();
        },
    },
    props: {
        aria2Version: {
            type: String,
            default: ""
        },
        ytdVersion: {
            type: String,
            default: ""
        },
    },
}
</script>
<style scoped lang="scss">
.system-info {
    display: flex;
    flex-direction: column;
    margin-top: 10px;

    .system-info-item {
        display: flex;
        flex-direction: row;
        margin-bottom: 10px;
    }

    .system-info-item-label {
        font-weight: bold;
        margin-right: 10px;
        display: flex;
        align-items: flex-end;
    }

    .system-info-item-value {
        font-weight: normal;

        .check-button {
            border-radius: 0.25em;
        }
    }
}
</style>
