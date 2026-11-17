const baseClasses = [
    'w-full min-w-0 bg-transparent border-0 p-0 text-foreground outline-none resize-none',
    'leading-[1.55] placeholder:text-muted-foreground/70',
    'disabled:cursor-not-allowed disabled:opacity-65 data-disabled:cursor-not-allowed data-disabled:opacity-65',
].join(' ');

const variantClasses = {
    default: '',
    panel: 'min-h-[220px] px-4 py-3 bg-white/5 border border-border-subtle rounded-[15px]',
};

/**
 * @param {{ variant?: keyof typeof variantClasses }} options
 * @returns {string}
 */
export function textareaVariants({ variant = 'default' } = {}) {
    return [
        baseClasses,
        variantClasses[variant] ?? variantClasses.default,
    ].join(' ');
}
