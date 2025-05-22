import { getConfig } from './Utils.js';

export const APIClient = {
    timeoutMs: 10000,
    debug: false,
    initialized: false,

    async init() {
        const config = await getConfig();
        this.debug = config?.debug_error?.mode === 'debug';
        this.initialized = true;
    },

    async send(module, action, data = null, expect = 'auto') {
        if (!this.initialized) {
            await this.init();
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeoutMs);

        try {
            if (this.debug) {
                console.log(`[API →] ${module}.${action}`, data);
            }

            const response = await fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ module, action, data }),
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            // if (!response.ok) {
            //     const result = await response.json();  // Сюда попадёт результат из ErrorHandler
            //     if (this.debug) {
            //         console.warn(`[API ❌ Error] ${module}.${action}`, result);
            //     }
            //     return result;
            // }

            const contentType = response.headers.get('content-type');
            let result;

            if (expect === 'text' || (expect === 'auto' && contentType?.includes('text/html'))) {
                result = await response.text();
            } else if (expect === 'json' || (expect === 'auto' && contentType?.includes('application/json'))) {
                result = await response.json();
            } else {
                result = await response.text();
            }

            if (this.debug) {
                const label = response.ok ? '[API ✔️ Parsed]' : '[API ❌ Error]';
                console.log(`${label} ${module}.${action}`, result);
            }

            return result;

        } catch (error) {
            clearTimeout(timeoutId);
            console.error(`[API ❌ Catch] ${module}.${action}`, error);
            return {
                status: 'error',
                code: 500,
                message: error.message || 'Ошибка запроса',
                extra: {
                    reason: 'network_error'
                }
            };
        }
    }
};
