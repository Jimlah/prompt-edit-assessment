import { announce, definePrimitive } from '@ui-sdk/core';

export type CopyButtonOptions = {
    value?: string;
    timeout?: number;
    disabled?: boolean;
    label?: string;
    copiedLabel?: string;
    onCopied?: (value: string) => void;
    onError?: (error: unknown) => void;
};

type CopyButtonState = {
    copied: boolean;
    value: string;
    disabled: boolean;
    label: string;
    copiedLabel: string;
};

function fallbackCopy(text: string): boolean {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
        return document.execCommand('copy');
    } finally {
        document.body.removeChild(textarea);
    }
}

export const copyButtonPrimitive = definePrimitive({
    name: 'copy-button',
    options: {
        value: 'string',
        timeout: 'number',
        disabled: 'boolean',
        label: 'string',
        copiedLabel: 'string',
        onCopied: 'callback',
        onError: 'callback',
    },
    state: (options: CopyButtonOptions): CopyButtonState => ({
        copied: false,
        value: options.value ?? '',
        disabled: Boolean(options.disabled),
        label: options.label || 'Copy to clipboard',
        copiedLabel: options.copiedLabel || 'Copied to clipboard',
    }),
    sync: (options: CopyButtonOptions): Partial<CopyButtonState> => ({
        value: options.value ?? '',
        disabled: Boolean(options.disabled),
        label: options.label || 'Copy to clipboard',
        copiedLabel: options.copiedLabel || 'Copied to clipboard',
    }),
    api: (context) => {
        let resetTimer: ReturnType<typeof setTimeout> | undefined;

        context.onDestroy(() => {
            clearTimeout(resetTimer);
        });

        return {
            async copy(): Promise<boolean> {
                const options = context.options.get();

                if (options.disabled || !options.value) {
                    return false;
                }

                try {
                    if (navigator.clipboard?.writeText) {
                        await navigator.clipboard.writeText(options.value);
                    } else if (!fallbackCopy(options.value)) {
                        throw new Error('Clipboard copy failed.');
                    }

                    context.store.set({ copied: true });
                    clearTimeout(resetTimer);
                    resetTimer = setTimeout(() => {
                        context.store.set({ copied: false });
                    }, options.timeout ?? 2000);

                    announce(options.copiedLabel || 'Copied to clipboard');
                    options.onCopied?.(options.value);

                    return true;
                } catch (error) {
                    announce('Unable to copy to clipboard.', { priority: 'assertive' });
                    options.onError?.(error);

                    return false;
                }
            },
        };
    },
    slots: (slot) => ({
        root: slot<Record<string, never>, { copied: boolean }>({
            state: (state) => ({ copied: state.copied }),
            attrs: (state) => {
                const disabled = state.disabled || !state.value;
                const label = state.copied ? state.copiedLabel : state.label;

                return {
                    type: 'button',
                    disabled,
                    'aria-pressed': String(state.copied),
                    'data-copied': state.copied ? '' : undefined,
                    'data-disabled': disabled ? '' : undefined,
                    'aria-label': label,
                    title: label,
                };
            },
            press: ({ api }) => api.copy(),
        }),
    }),
});
