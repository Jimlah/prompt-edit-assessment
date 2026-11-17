const variantClasses = {
    default: 'rounded-full border-white/20 bg-white/10 text-white backdrop-blur-md placeholder:text-white/70 hover:border-white/30',
    light: 'rounded-xl border-neutral-300 bg-white text-neutral-950 placeholder:text-neutral-500 hover:border-neutral-400',
};

const focusClasses = {
    default: 'focus-visible:border-accent focus-visible:outline-accent',
    light: 'focus-visible:border-accent focus-visible:outline-neutral-950',
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
 * @param {{ variant?: keyof typeof variantClasses, size?: keyof typeof sizeClasses, grouped?: boolean }} options
 * @returns {string}
 */
export function textFieldVariants({ variant = 'default', size = 'default', grouped = false } = {}) {
    if (grouped) {
        return 'h-full min-w-0 flex-1 rounded-none border-0 bg-transparent px-0 text-current shadow-none placeholder:text-current/60 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 data-disabled:cursor-not-allowed data-disabled:opacity-50';
    }

    return [
        'w-full min-w-0 border px-5 shadow-sm transition-[color,background-color,border-color,box-shadow] duration-200',
        'focus-visible:outline-2 focus-visible:outline-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-50 data-disabled:cursor-not-allowed data-disabled:opacity-50',
        'aria-invalid:border-red-500 aria-invalid:focus-visible:outline-red-500',
        variantClasses[variant] ?? variantClasses.default,
        focusClasses[variant] ?? focusClasses.default,
        sizeClasses[size] ?? sizeClasses.default,
    ].join(' ');
}

/**
 * @param {{ variant?: keyof typeof variantClasses, size?: keyof typeof sizeClasses }} options
 * @returns {string}
 */
export function textFieldGroupVariants({ variant = 'default', size = 'default' } = {}) {
    return [
        'flex w-full min-w-0 items-center gap-3 border px-5 shadow-sm transition-[color,background-color,border-color,box-shadow] duration-200',
        'has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2',
        'data-disabled:cursor-not-allowed data-disabled:opacity-50',
        '[&>svg]:size-5 [&>svg]:shrink-0',
        variantClasses[variant] ?? variantClasses.default,
        groupFocusClasses[variant] ?? groupFocusClasses.default,
        sizeClasses[size] ?? sizeClasses.default,
    ].join(' ');
}
