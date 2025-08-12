<template>
  <section>
    <h2>General Settings</h2>
    <NcTextField v-model="form.vpnStartCmd" label="VPN start command" />
    <NcTextField v-model="form.vpnStopCmd" label="VPN stop command" />
    <NcTextField v-model="form.downloadProxy" label="Download Proxy" placeholder="socks5://127.0.0.1:1080" />
    <NcButton :disabled="saving" @click="save">Save</NcButton>
  </section>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { NcTextField, NcButton } from '@nextcloud/vue'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import api from '../api/settings'

const form = ref({ vpnStartCmd: '', vpnStopCmd: '', downloadProxy: '' })
const saving = ref(false)

onMounted(async () => {
  try {
    const data = await api.getPersonal()
    form.value = data
  } catch (e) {
    showError(t('ncdownloader', 'Could not load settings'))
  }
})

async function save() {
  try {
    saving.value = true
    await api.savePersonal(form.value)
    showSuccess(t('ncdownloader', 'Saved'))
  } catch (e) {
    showError(t('ncdownloader', 'Save failed'))
  } finally {
    saving.value = false
  }
}
</script>
