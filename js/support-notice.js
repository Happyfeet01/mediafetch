(() => {
    async function init() {
        const notice = document.getElementById('mediafetch-support-notice');
        if (!notice) return;
        const url = OC.generateUrl('/apps/mediafetch/support-notice');
        const headers = { requesttoken: document.head.dataset.requesttoken };
        try {
            const response = await fetch(url, { headers, cache: 'no-store' });
            if (!response.ok || !(await response.json()).visible) return;
        } catch {
            return;
        }
        const buttons = notice.querySelectorAll('button');
        const error = notice.querySelector('[role="status"]');
        notice.addEventListener('click', async event => {
            const button = event.target.closest('button[data-duration]');
            if (!button) return;
            buttons.forEach(item => { item.disabled = true; });
            error.hidden = true;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers,
                    body: new URLSearchParams({ duration: button.dataset.duration }),
                });
                if (!response.ok || response.redirected) throw new Error('Dismissal failed');
                notice.hidden = true;
            } catch {
                error.hidden = false;
            } finally {
                buttons.forEach(item => { item.disabled = false; });
            }
        });
        notice.hidden = false;
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
