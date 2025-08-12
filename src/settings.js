import './css/autoComplete.css'
import './css/settings.scss'
import { createApp } from 'vue'
import adminSettings from './adminSettings'
import Settings from './views/Settings.vue'

createApp(adminSettings).mount('#ncdownloader-admin-settings')
createApp(Settings).mount('#ncdownloader-personal-settings')
