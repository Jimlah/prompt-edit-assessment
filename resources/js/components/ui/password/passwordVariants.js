const variantClasses = {
    default: 'rounded-full border-white/20 bg-white/10 text-white backdrop-blur-md placeholder:text-white/70 hover:border-white/30',
    light: 'rounded-xl border-neutral-300 bg-white text-neutral-950 placeholder:text-neutral-500 hover:border-neutral-400',
};

const groupFocusClasses = {
    default: 'has-[:focus-visible]:border-accent has-[:focus-visible]:outline-accent',
    light: 'has-[:focus-visible]:border-accent has-[:focus-visible]:outline-neutral-950',
};

const sizeClasses = {
    sm: 'h-10 text-base sm:text-sm',
    default: 'h-12 text-base sm:text-sm',
    lg: 'h-14 text-base',
};

/**
 * @param {{ variant?: keyof typeof variantClasses, size?: keyof typeof sizeClasses }} options
 * @returns {string}
 */
export function passwordVariants({ variant = 'default', size = 'default' } = {}) {
    return [
        'flex w-full min-w-0 items-center gap-2 border ps-5 pe-1.5 shadow-sm transition-[color,background-color,border-color,box-shadow] duration-200',
        'has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2',
        'data-disabled:cursor-not-allowed data-disabled:opacity-50',
        variantClasses[variant] ?? variantClasses.default,
        groupFocusClasses[variant] ?? groupFocusClasses.default,
        sizeClasses[size] ?? sizeClasses.default,
    ].join(' ');
}
