const variantClasses = {
    default: 'rounded-3xl border-white/20 bg-white/10 text-white backdrop-blur-md placeholder:text-white/70 hover:border-white/30',
    light: 'rounded-2xl border-neutral-300 bg-white text-neutral-950 placeholder:text-neutral-500 hover:border-neutral-400',
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
    sm: 'min-h-24 text-base sm:text-sm',
    default: 'min-h-32 text-base sm:text-sm',
    lg: 'min-h-44 text-base',
};

/**
 * @param {{ variant?: keyof typeof variantClasses, size?: keyof typeof sizeClasses, grouped?: boolean }} options
 * @returns {string}
 */
export function textareaVariants({ variant = 'default', size = 'default', grouped = false } = {}) {
    const classes = [
        'block w-full min-w-0 transition-[color,background-color,border-color,box-shadow] duration-200',
        'disabled:cursor-not-allowed disabled:opacity-50 data-disabled:cursor-not-allowed data-disabled:opacity-50',
        sizeClasses[size] ?? sizeClasses.default,
    ];

    if (grouped) {
        classes.push('resize-none rounded-none border-0 bg-transparent p-0 text-current shadow-none placeholder:text-current/70 focus-visible:outline-none');
    } else {
        classes.push(
            'resize-y border px-5 py-4 shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2',
            'aria-invalid:border-red-500 aria-invalid:focus-visible:outline-red-500',
            variantClasses[variant] ?? variantClasses.default,
            focusClasses[variant] ?? focusClasses.default,
        );
    }

    return classes.join(' ');
}

/**
 * @param {{ variant?: keyof typeof variantClasses }} options
 * @returns {string}
 */
export function textareaGroupVariants({ variant = 'default' } = {}) {
    return [
        'flex w-full min-w-0 flex-col gap-4 border p-5 shadow-sm transition-[color,background-color,border-color,box-shadow] duration-200',
        'has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2',
        '[&:has([data-disabled])]:cursor-not-allowed [&:has([data-disabled])]:opacity-50',
        variantClasses[variant] ?? variantClasses.default,
        groupFocusClasses[variant] ?? groupFocusClasses.default,
    ].join(' ');
}
