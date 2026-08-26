import helper from './utils/helper'
import eventHandler from './lib/eventHandler'
import { translate as t } from '@nextcloud/l10n'
import updatePage from './actions/updatePage'
import buttonActions from './actions/buttonActions'
import './css/style.scss'
import './css/table.scss'
import { createApp } from 'vue'
import App from './App'
import { delegate } from 'tippy.js'
import 'tippy.js/dist/tippy.css'
import settingsBar from './settingsBar'

const APP_ID = 'mediafetch'
const basePath = `/apps/${APP_ID}`
const navigationOpenClass = 'mediafetch-navigation-open'

window.addEventListener('DOMContentLoaded', function () {
    helper.showErrors('[data-error-message]')
    updatePage.run()
    buttonActions.run()

    const container = 'ncdownloader-form-wrapper'
    const dataContainerID = 'app-settings-data'
    const app = createApp(App)
    const bar = createApp(settingsBar)
    const dataContainer = document.getElementById(dataContainerID)

    let values = {}
    try {
        const settings = dataContainer.getAttribute('data-settings')
        const searchSites = dataContainer.getAttribute('data-search-sites')
        values.settings = JSON.parse(settings)
        values.search_sites = JSON.parse(searchSites)
    } catch (e) {
        values = {}
        console.log(e)
    }

    bar.provide('settings', values.settings)
    bar.mount('#app-settings-content')
    app.provide('settings', values)
    const vm = app.mount(`#${container}`)
    helper.addVue(vm.$options.name, vm)

    // Nextcloud 34 still renders the legacy app-navigation toggle for apps
    // using #app-navigation, but no longer wires the old Snap.js behaviour.
    // Keep the legacy markup for compatibility and handle the drawer locally.
    const navigationToggle = document.getElementById('app-navigation-toggle')
    const navigation = document.getElementById('app-navigation')

    const setNavigationOpen = (open) => {
        document.body.classList.toggle(navigationOpenClass, open)
        if (navigationToggle) {
            navigationToggle.setAttribute('aria-expanded', open ? 'true' : 'false')
        }
    }

    if (navigationToggle && navigation) {
        navigationToggle.setAttribute('aria-expanded', 'false')
        navigationToggle.setAttribute('aria-label', t(APP_ID, 'Toggle navigation'))

        navigationToggle.addEventListener('click', (event) => {
            event.preventDefault()
            event.stopPropagation()
            setNavigationOpen(!document.body.classList.contains(navigationOpenClass))
        })

        document.addEventListener('click', (event) => {
            if (!document.body.classList.contains(navigationOpenClass)) {
                return
            }
            if (!navigation.contains(event.target) && !navigationToggle.contains(event.target)) {
                setNavigationOpen(false)
            }
        })

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setNavigationOpen(false)
            }
        })
    }

    eventHandler.add('click', '#start-aria2', 'button', function (e) {
        const path = `${basePath}/aria2/start`
        const element = e.target
        if (element.classList.contains('notinstalled')) {
            return
        }

        const parent = element.parentElement
        const oldHtml = parent.innerHTML
        parent.innerHTML = helper.loadingTpl()
        const url = helper.generateUrl(path)

        const callback = function (parent, html, data) {
            parent.innerHTML = html
            if (!data.status) {
                if (data.error) helper.error(data.error)
                return
            }

            const button = document.querySelector('#start-aria2 button')
            const aria2 = button.getAttribute('data-aria2')
            if (!aria2) return

            if (aria2 === 'on') {
                button.setAttribute('data-aria2', 'off')
                button.textContent = t(APP_ID, 'Start Aria2')
            } else {
                button.setAttribute('data-aria2', 'on')
                button.textContent = t(APP_ID, 'Stop Aria2')
            }
        }

        helper.httpClient(url).setHandler(function (data) {
            callback(parent, oldHtml, data)
        }).send()
    })

    eventHandler.add('click', '#app-navigation', '#search-download', helper.showDownload)
    delegate('#app-ncdownloader-wrapper', { target: '[data-tippy-content]' })
})
