'use strict'

const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const root = path.resolve(__dirname, '..')
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8')
const catalog = JSON.parse(read('l10n/de.json'))

assert.equal(catalog.pluralForm, 'nplurals=2; plural=(n != 1);')
assert.ok(catalog.translations && typeof catalog.translations === 'object')

let registered
vm.runInNewContext(read('l10n/de.js'), {
  OC: {
    L10N: {
      register(app, translations, pluralForm) {
        registered = { app, translations, pluralForm }
      },
    },
  },
})
assert.equal(registered.app, 'mediafetch')
assert.equal(registered.pluralForm, catalog.pluralForm)
assert.deepEqual(JSON.parse(JSON.stringify(registered.translations)), catalog.translations)

// Ensure the two Nextcloud catalogs do not silently diverge, or contain
// mismatched interpolation placeholders that would leak raw tokens into UI.
for (const [source, translated] of Object.entries(catalog.translations)) {
  assert.equal(typeof translated, 'string', 'Invalid translation: ' + source)
  assert.ok(translated.trim().length > 0, 'Empty translation: ' + source)
  const placeholders = (value) => [...value.matchAll(/\{[a-zA-Z][a-zA-Z0-9_]*\}/g)]
    .map((match) => match[0]).sort()
  assert.deepEqual(placeholders(source), placeholders(translated),
    'Mismatched placeholders in: ' + source)
}

const files = [
  'src/App.vue',
  'src/index.js',
  'src/settingsBar.vue',
  'src/adminSettings.vue',
  'src/personalSettings.vue',
  'src/components/ytdlOptionsWizard.vue',
  'src/components/mainForm.vue',
  'src/components/searchInput.vue',
  'src/components/systemInfo.vue',
  'src/components/folderSettings.vue',
  'src/components/customOptions.vue',
  'src/components/editableRow.vue',
  'src/components/settingsRow.vue',
  'src/components/actionButton.vue',
  'src/components/uploadFile.vue',
  'src/lib/settingsForm.ts',
  'src/actions/buttonActions.js',
]

const required = new Set([
  // PHP template navigation labels.
  'Active Downloads',
  'Waiting Downloads',
  'Failed Downloads',
  'Complete Downloads',
  'yt-dlp Downloads',
  'Download & Search',
  'Start Aria2',
  'Stop Aria2',
  'Settings',
  // PHP-generated setting titles, localized by the Vue settings rows.
  'Downloads Folder',
  'Torrents Folder',
  'Download Proxy',
  'Aria2 RPC Host',
  'Aria2 RPC Port',
  'Aria2 RPC Token',
  'yt-dlp binary',
  'Aria2c binary',
  // Backend status strings shown on the main download list.
  'Adding to Nextcloud…',
  'Downloading…',
])

// Match literal translation calls; dynamic server or user content is not a
// catalog key. The base locale is English, so no en.json is necessary.
const sources = [
  /\btr\(\s*(['"])((?:\\.|(?!\1)[^\\])*?)\1\s*\)/g,
  /\bhelper\.t\(\s*(['"])((?:\\.|(?!\1)[^\\])*?)\1\s*\)/g,
  /\bt\(\s*(?:APP_ID|['"]mediafetch['"])\s*,\s*(['"])((?:\\.|(?!\1)[^\\])*?)\1/g,
]
for (const file of files) {
  const data = read(file)
  for (const regex of sources) {
    for (const match of data.matchAll(regex)) {
      // Skip only literal text, never arbitrary content passed for translation.
      const key = match[2].replace(/\\'/g, "'").replace(/\\"/g, '"')
      required.add(key)
    }
  }
}
const missing = [...required].filter((key) => !(key in catalog.translations))
assert.deepEqual(missing, [], 'Missing German translations')
console.log('MediaFetch l10n verified: ' + Object.keys(catalog.translations).length + ' German strings, English source fallback.')
