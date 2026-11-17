const variantClasses = {
    default: 'border-white/25 bg-white/10 data-checked:border-accent data-checked:bg-accent data-indeterminate:border-accent data-indeterminate:bg-accent',
    light: 'border-neutral-300 bg-white data-checked:border-accent data-checked:bg-accent data-indeterminate:border-accent data-indeterminate:bg-accent',
};

/**
 * @param {{ variant?: keyof typeof variantClasses }} options
 * @returns {string}
 */
export function checkboxVariants({ variant = 'default' } = {}) {
    return [
        'relative inline-flex size-5 shrink-0 items-center justify-center rounded-md border text-accent-foreground shadow-sm transition-[color,background-color,border-color,opacity] duration-150',
        'before:absolute before:-inset-2.5 before:content-[""]',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
        'data-disabled:cursor-not-allowed data-disabled:opacity-50 data-readonly:cursor-default',
        variantClasses[variant] ?? variantClasses.default,
    ].join(' ');
}
