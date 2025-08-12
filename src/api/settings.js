import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export function getPersonal() {
    return axios.get(generateUrl('/apps/ncdownloader/settings/personal')).then(r => r.data)
}

export function savePersonal(data) {
    return axios.post(generateUrl('/apps/ncdownloader/settings/personal'), data).then(r => r.data)
}

export default { getPersonal, savePersonal }
