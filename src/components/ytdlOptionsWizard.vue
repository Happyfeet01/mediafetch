<template>
  <div class="section mediafetch-ytdlp-builder">
    <h3>{{ tr('yt-dlp configuration assistant') }}</h3>
    <p>{{ tr('Configure common options visually. All settings apply to your personal yt-dlp downloads. Advanced options remain available below.') }}</p>

    <div v-if="loading">{{ tr('Loading current settings…') }}</div>
    <div v-else>
      <div class="mediafetch-builder-grid">
        <label>
          {{ tr('Quick preset') }}
          <select v-model="preset" @change="applyPreset">
            <option value="">{{ tr('Custom / current settings') }}</option>
            <option value="music">{{ tr('Music: MP3, metadata and cover') }}</option>
            <option value="audio">{{ tr('Audio: M4A') }}</option>
            <option value="video">{{ tr('Video: up to 1080p') }}</option>
          </select>
        </label>
        <label>
          {{ tr('Download playlists') }}
          <select v-model="playlistMode">
            <option value="auto">{{ tr('Use yt-dlp default') }}</option>
            <option value="yes">{{ tr('Entire playlist') }}</option>
            <option value="no">{{ tr('Single item only') }}</option>
          </select>
        </label>
        <label>
          {{ tr('Audio extraction / conversion') }}
          <select v-model="audioMode">
            <option value="">{{ tr('No audio extraction') }}</option>
            <option value="best">{{ tr('Audio only (best available)') }}</option>
            <option value="mp3">MP3</option>
            <option value="m4a">M4A</option>
            <option value="opus">Opus</option>
            <option value="vorbis">Vorbis</option>
            <option value="flac">FLAC</option>
          </select>
        </label>
        <label v-if="audioMode">
          {{ tr('Audio conversion quality') }}
          <select v-model="audioQuality">
            <option value="">{{ tr('yt-dlp default') }}</option>
            <option value="0">{{ tr('Highest VBR quality (0)') }}</option>
            <option value="5">{{ tr('Medium VBR quality (5)') }}</option>
            <option value="9">{{ tr('Lowest VBR quality (9)') }}</option>
          </select>
        </label>
        <label>
          {{ tr('Video format / quality') }}
          <select v-model="videoMode">
            <option value="">{{ tr('Use existing downloader defaults') }}</option>
            <option value="1080">{{ tr('Up to 1080p') }}</option>
            <option value="720">{{ tr('Up to 720p') }}</option>
            <option value="custom">{{ tr('Custom format expression') }}</option>
          </select>
        </label>
        <label v-if="videoMode === 'custom'">
          {{ tr('yt-dlp format expression') }}
          <input v-model.trim="customVideoFormat" type="text" placeholder="bv*+ba/b" />
        </label>
        <label>
          {{ tr('Container after merging video streams') }}
          <select v-model="mergeFormat">
            <option value="">{{ tr('yt-dlp default') }}</option>
            <option value="mp4">MP4</option>
            <option value="mkv">MKV</option>
            <option value="webm">WebM</option>
          </select>
        </label>
      </div>

      <fieldset>
        <legend>{{ tr('Metadata and subtitles') }}</legend>
        <label><input v-model="embedMetadata" type="checkbox" /> {{ tr('Embed available metadata') }}</label>
        <label><input v-model="embedThumbnail" type="checkbox" /> {{ tr('Embed thumbnail / cover') }}</label>
        <label><input v-model="writeSubs" type="checkbox" /> {{ tr('Download subtitles') }}</label>
        <label><input v-model="writeAutoSubs" type="checkbox" /> {{ tr('Include automatic subtitles') }}</label>
        <label><input v-model="embedSubs" type="checkbox" /> {{ tr('Embed subtitles if supported') }}</label>
        <label>
          {{ tr('Subtitle languages (optional)') }}
          <input v-model.trim="subtitleLanguages" type="text" placeholder="de,en" />
        </label>
      </fieldset>

      <div class="mediafetch-builder-grid">
        <label>
          {{ tr('Speed limit (optional)') }}
          <input v-model.trim="limitRate" type="text" :placeholder="tr('e.g. 2M')" />
        </label>
        <label>
          {{ tr('Retries (optional)') }}
          <input v-model.trim="retries" type="number" min="0" step="1" placeholder="yt-dlp default" />
        </label>
        <label class="mediafetch-builder-wide">
          {{ tr('Filename template (optional, filename only)') }}
          <input v-model.trim="filenameTemplate" type="text" placeholder="%(title)s.%(ext)s" />
        </label>
        <label class="mediafetch-builder-wide">
          {{ tr('Artist and title parsing rule (optional)') }}
          <input v-model.trim="parseMetadata" type="text" placeholder="%(title)s:(?P<artist>.+?) - (?P<title>.+)" />
          <span class="mediafetch-builder-help">{{ tr('Use FROM:TO, e.g. to read Artist - Title from a video title. Filename patterns belong in the field above.') }}</span>
        </label>
      </div>

      <details>
        <summary>{{ tr('Preview yt-dlp options') }}</summary>
        <code class="mediafetch-builder-preview">{{ commandPreview }}</code>
        <p>{{ tr('These are persistent user settings, not a separate per-download command. The per-download profile in the main form may override these choices for one download.') }}</p>
      </details>

      <div class="mediafetch-builder-actions">
        <button type="button" :disabled="saving" @click="save">
          {{ saving ? tr('Saving…') : tr('Save yt-dlp settings') }}
        </button>
        <button type="button" :disabled="saving" @click="reload">{{ tr('Discard changes') }}</button>
      </div>
      <p class="mediafetch-builder-note">{{ tr('Unknown / advanced safe parameters are kept when saving. To edit them, use the advanced option list below.') }}</p>
    </div>
  </div>
</template>

<script>
import helper from '../utils/helper'
import safeYtdlOptions from '../utils/safeYtdlOptions'
import managedYtdlOptions from '../utils/managedYtdlOptions'

const format1080 = 'bv*[height<=1080]+ba/b[height<=1080]'
const format720 = 'bv*[height<=720]+ba/b[height<=720]'
const has = (obj, key) => Object.prototype.hasOwnProperty.call(obj, key)



export default {
  name: 'ytdlOptionsWizard',
  data() {
    return {
      loading: true,
      saving: false,
      saved: {},
      preset: '',
      playlistMode: 'auto',
      audioMode: '',
      audioQuality: '',
      videoMode: '',
      customVideoFormat: '',
      mergeFormat: '',
      embedMetadata: false,
      embedThumbnail: false,
      writeSubs: false,
      writeAutoSubs: false,
      embedSubs: false,
      subtitleLanguages: '',
      limitRate: '',
      retries: '',
      filenameTemplate: '',
      parseMetadata: '',
    }
  },
  computed: {
    options() {
      const options = { ...this.saved }
      managedYtdlOptions.forEach((key) => delete options[key])

      if (this.playlistMode === 'yes') options['yes-playlist'] = ''
      if (this.playlistMode === 'no') options['no-playlist'] = ''

      if (this.audioMode) {
        options['extract-audio'] = ''
        if (this.audioMode !== 'best') options['audio-format'] = this.audioMode
        if (this.audioQuality !== '') options['audio-quality'] = this.audioQuality
      }

      const videoFormat = this.videoMode === '1080' ? format1080
        : this.videoMode === '720' ? format720
          : this.videoMode === 'custom' ? this.customVideoFormat : ''
      if (videoFormat) options.format = videoFormat
      if (this.mergeFormat) options['merge-output-format'] = this.mergeFormat

      if (this.embedMetadata) options['embed-metadata'] = ''
      if (this.embedThumbnail) options['embed-thumbnail'] = ''
      if (this.writeSubs) options['write-subs'] = ''
      if (this.writeAutoSubs) options['write-auto-subs'] = ''
      if (this.embedSubs) options['embed-subs'] = ''
      if (this.subtitleLanguages) options['sub-langs'] = this.subtitleLanguages
      if (this.limitRate) options['limit-rate'] = this.limitRate
      if (this.retries !== '') options.retries = String(this.retries)
      if (this.filenameTemplate) options.output = this.filenameTemplate
      if (this.parseMetadata) options['parse-metadata'] = this.parseMetadata
      return options
    },
    commandPreview() {
      return Object.entries(this.options)
        .filter(([key]) => safeYtdlOptions.includes(key))
        .map(([key, value]) => '--' + key + (value === '' ? '' : ' ' + JSON.stringify(String(value))))
        .join(' ') || this.tr('(No custom yt-dlp settings)')
    },
  },
  methods: {
    tr(message) { return helper.t(message) },
    reload() {
      this.loading = true
      helper.httpClient(helper.generateUrl('/apps/mediafetch/personal/ytdl/get'))
        .setHandler((data) => {
          this.saved = data && typeof data === 'object' && !Array.isArray(data) ? { ...data } : {}
          this.populate()
          this.loading = false
        })
        .setErrorHandler(() => {
          this.loading = false
          helper.error(this.tr('Could not load yt-dlp settings.'))
        })
        .send()
    },
    populate() {
      const values = this.saved
      this.preset = ''
      this.playlistMode = has(values, 'yes-playlist') ? 'yes'
        : has(values, 'no-playlist') ? 'no' : 'auto'
      this.audioMode = has(values, 'extract-audio') ? (values['audio-format'] || 'best') : ''
      this.audioQuality = values['audio-quality'] === undefined ? '' : String(values['audio-quality'])
      this.videoMode = values.format === format1080 ? '1080'
        : values.format === format720 ? '720' : values.format ? 'custom' : ''
      this.customVideoFormat = this.videoMode === 'custom' ? values.format : ''
      this.mergeFormat = values['merge-output-format'] || ''
      this.embedMetadata = has(values, 'embed-metadata')
      this.embedThumbnail = has(values, 'embed-thumbnail')
      this.writeSubs = has(values, 'write-subs')
      this.writeAutoSubs = has(values, 'write-auto-subs')
      this.embedSubs = has(values, 'embed-subs')
      this.subtitleLanguages = values['sub-langs'] || ''
      this.limitRate = values['limit-rate'] || ''
      this.retries = values.retries === undefined ? '' : String(values.retries)
      this.filenameTemplate = values.output || ''
      this.parseMetadata = values['parse-metadata'] || ''
    },
    applyPreset() {
      if (this.preset === 'music') {
        this.playlistMode = 'yes'
        this.audioMode = 'mp3'
        this.audioQuality = '0'
        this.embedMetadata = true
        this.embedThumbnail = true
        this.videoMode = ''
        this.mergeFormat = ''
      } else if (this.preset === 'audio') {
        this.audioMode = 'm4a'
        this.audioQuality = ''
        this.videoMode = ''
        this.mergeFormat = ''
      } else if (this.preset === 'video') {
        this.audioMode = ''
        this.audioQuality = ''
        this.videoMode = '1080'
        this.mergeFormat = 'mp4'
      }
    },
    save() {
      if (this.saving) return
      if (this.filenameTemplate &&
          (this.filenameTemplate.includes('..') || /[/\\\0]/.test(this.filenameTemplate))) {
        helper.error(this.tr('Filename template must not contain a directory or "..".'))
        return
      }
      if (this.parseMetadata) {
        const separator = this.parseMetadata.indexOf(':')
        if (separator < 1 || separator >= this.parseMetadata.length - 1) {
          helper.error(this.tr('Metadata parsing needs FROM:TO. Put filename templates in the Filename template field.'))
          return
        }
      }
      if (this.retries !== '' && (!/^\d+$/.test(String(this.retries)))) {
        helper.error(this.tr('Retries must be a non-negative whole number.'))
        return
      }
      this.saving = true
      helper.httpClient(helper.generateUrl('/apps/mediafetch/personal/ytdl/save'))
        .setData(this.options)
        .setHandler((response) => {
          this.saving = false
          if (!response || response.error) {
            helper.error(response?.error || this.tr('Could not save yt-dlp settings.'))
            return
          }
          helper.info(this.tr('yt-dlp settings saved.'))
          // The existing advanced editor reads its own copy at mount time.
          // Reload so both editors show exactly the same stored settings.
          window.location.reload()
        })
        .setErrorHandler(() => {
          this.saving = false
          helper.error(this.tr('Could not save yt-dlp settings.'))
        })
        .send()
    },
  },
  mounted() {
    this.reload()
  },
}
</script>

<style scoped lang="scss">
.mediafetch-ytdlp-builder {
  max-width: 880px;

  p,
  .mediafetch-builder-note {
    color: var(--color-text-maxcontrast);
  }

  .mediafetch-builder-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin: 14px 0;
  }

  label {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin: 8px 0;
    color: var(--color-main-text);
  }

  input:not([type="checkbox"]),
  select {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    min-height: 38px;
    background: var(--color-main-background);
    color: var(--color-main-text);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-element);
    padding: 6px;
  }

  fieldset {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-large);
    padding: 12px;
  }

  fieldset label {
    flex-direction: row;
    align-items: center;
    flex-wrap: wrap;
  }

  fieldset input[type="text"] {
    flex-basis: 100%;
  }

  .mediafetch-builder-wide {
    grid-column: 1 / -1;
  }

  .mediafetch-builder-help {
    color: var(--color-text-maxcontrast);
    font-size: 13px;
    line-height: 1.45;
  }

  details {
    margin: 16px 0;
  }

  .mediafetch-builder-preview {
    display: block;
    padding: 10px;
    border-radius: 6px;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    color: var(--color-main-text);
    background: var(--color-background-dark);
  }

  .mediafetch-builder-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
  }

  .mediafetch-builder-actions button {
    padding: 8px 14px;
  }
}
</style>
