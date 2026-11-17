const baseClasses = [
    'flex min-h-[47px] flex-col items-center justify-center gap-0.5 px-3 py-2.5',
    'text-[13px] text-muted-foreground cursor-pointer bg-white/5 border border-transparent rounded-[11px]',
    'disabled:cursor-not-allowed disabled:opacity-58 data-disabled:cursor-not-allowed data-disabled:opacity-58',
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
    'data-pressed:text-primary-foreground data-pressed:bg-primary',
].join(' ');

const variantClasses = {
    default: '',
    compact: 'min-h-[38px] px-2 py-1.5 text-xs',
};

/**
 * @param {{ variant?: keyof typeof variantClasses }} options
 * @returns {string}
 */
export function toggleVariants({ variant = 'default' } = {}) {
    return [
        baseClasses,
        variantClasses[variant] ?? variantClasses.default,
    ].join(' ');
}
