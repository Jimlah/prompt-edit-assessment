import { createToaster } from '@ui-sdk/vue/toast';

export const toaster = createToaster({
    duration: 4000,
});

export function notify(message, type = 'success') {
    const toastType = ['success', 'info', 'warning', 'error'].includes(type) ? type : 'info';

    toaster[toastType](message);
}
