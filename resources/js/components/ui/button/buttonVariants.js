const baseClasses = [
    'inline-flex min-w-0 shrink-0 items-center justify-center gap-2',
    'rounded-xl text-sm font-semibold whitespace-nowrap',
    'transition-[color,background-color,border-color,box-shadow,transform] duration-200',
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current',
    'disabled:cursor-not-allowed disabled:opacity-50 data-disabled:cursor-not-allowed data-disabled:opacity-50',
    'data-pending:cursor-wait data-pending:opacity-75',
    '[&_svg]:size-4 [&_svg]:shrink-0',
].join(' ');

const variantClasses = {
    default: 'border border-transparent bg-accent text-accent-foreground shadow-lg shadow-accent/25 hover:bg-accent-hover active:translate-y-px',
    destructive: 'border border-transparent bg-red-600 text-white shadow-sm hover:bg-red-700 active:translate-y-px',
    outline: 'border border-white/30 bg-transparent text-white hover:bg-white/10',
    secondary: 'border border-white/15 bg-white/10 text-white shadow-sm backdrop-blur-md hover:border-white/25 hover:bg-white/15',
    ghost: 'bg-transparent text-white hover:bg-white/10',
    link: 'bg-transparent px-0 text-accent underline-offset-4 hover:underline',
};

const sizeClasses = {
    default: 'h-11 px-5 py-2.5',
    sm: 'h-9 rounded-lg px-3.5 text-xs',
    lg: 'h-12 px-6 text-base',
    icon: 'size-11 p-0',
};

/**
 * @param {{ variant?: keyof typeof variantClasses, size?: keyof typeof sizeClasses }} options
 * @returns {string}
 */
export function buttonVariants({ variant = 'default', size = 'default' } = {}) {
    return [
        baseClasses,
        variantClasses[variant] ?? variantClasses.default,
        sizeClasses[size] ?? sizeClasses.default,
    ].join(' ');
}
