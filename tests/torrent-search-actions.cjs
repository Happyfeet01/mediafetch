const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')
const source = fs.readFileSync('src/actions/buttonActions.js', 'utf8')
  .replace(/^import .*$/gm, '').replace('export default {', 'const actions = {')
let handler, payload, removed = false, errorHandler, responseHandler, copies = 0
const row = { dataset: { link: 'magnet:?xt=urn:btih:0123456789012345678901234567890123456789' }, remove() { removed = true } }
const button = { disabled: false, classList: { contains: () => false },
  getAttribute: key => key === 'path' ? '/nextcloud/index.php/apps/mediafetch/new' : 'download-action-button',
  closest: selector => selector === '.table-row[data-link]' ? row : null }
const helper = { loop() {}, getCounters() {}, setContentTableType() {}, t: value => value,
  error() {}, message() {}, httpClient(url) {
    assert.equal(url, '/nextcloud/index.php/apps/mediafetch/new')
    return { setErrorHandler(fn) { errorHandler = fn; return this }, setHandler(fn) { responseHandler = fn; return this },
      setData(data) { payload = data; return this }, send() {} }
  } }
vm.runInNewContext(source, { helper, console, Http: {}, Clipboard: class { Copy() { copies++ } },
  eventHandler: { add(type, target, selector, fn) { if (!handler) handler = fn } } })
// Register the actual production handlers.
vm.runInNewContext(source + '\nactions.run();', { helper, console, Http: {}, Clipboard: class { Copy() { copies++ } },
  eventHandler: { add(type, target, selector, fn) { if (!handler) handler = fn } } })
const event = { target: { closest: () => button }, stopPropagation() {}, preventDefault() {} }
handler(event)
assert.deepEqual(JSON.parse(JSON.stringify(payload)), { url: row.dataset.link })
assert.equal(button.disabled, true)
responseHandler({ error: 'failure' })
assert.equal(button.disabled, false)
assert.equal(removed, false)
handler(event)
errorHandler({ status: 404 })
assert.equal(button.disabled, false)
assert.equal(removed, false)
button.classList.contains = () => true
handler(event)
assert.equal(copies, 1)
assert.equal(removed, false)
button.classList.contains = () => false
handler(event)
responseHandler({ result: 'gid' })
assert.equal(removed, true)
console.log('Torrent search: nested click, magnet submission, clipboard, success and failure passed')
