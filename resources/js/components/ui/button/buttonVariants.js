const baseClasses = [
    'inline-flex min-w-0 shrink-0 items-center justify-center gap-2',
    'rounded-xl text-sm font-semibold whitespace-nowrap',
    'transition-[color,background-color,border-color,box-shadow,transform] duration-200 motion-reduce:transition-none',
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current',
    'disabled:cursor-not-allowed disabled:opacity-50 data-disabled:cursor-not-allowed data-disabled:opacity-50',
    'data-pending:cursor-wait data-pending:opacity-75',
    '[&_svg]:shrink-0',
].join(' ');

const variantClasses = {
    default: 'border border-transparent bg-accent text-accent-foreground shadow-lg shadow-accent/25 hover:bg-accent-hover active:translate-y-px',
    destructive: 'border border-transparent bg-red-600 text-white shadow-sm hover:bg-red-700 active:translate-y-px',
    outline: 'border border-white/30 bg-transparent text-white hover:bg-white/10',
    secondary: 'border border-white/15 bg-white/10 text-white shadow-sm backdrop-blur-md hover:border-white/25 hover:bg-white/15',
    ghost: 'bg-transparent text-foreground hover:bg-foreground/10',
    link: 'bg-transparent px-0 text-accent underline-offset-4 hover:underline',
    primary: 'border border-transparent bg-primary text-primary-foreground shadow-[0_12px_28px_rgb(255_193_0/10%)] hover:-translate-y-0.5 hover:bg-primary-hover active:translate-y-px',
    chip: 'rounded-full border border-border-subtle bg-secondary text-foreground font-normal text-[clamp(12px,0.79vw,16px)] min-h-[clamp(37px,2.25vw,46px)] py-2 px-[clamp(14px,1.15vw,23px)] hover:bg-white/38 dark:hover:bg-white/13',
    circle: 'inline-grid place-items-center rounded-full size-[clamp(42px,2.75vw,56px)] border border-border bg-secondary text-foreground p-0 hover:-translate-y-px hover:bg-white/38 dark:hover:bg-white/15',
    panel: 'inline-grid place-items-center rounded-xl border border-border-subtle bg-secondary text-foreground p-0 hover:bg-foreground/10',
    tool: 'inline-grid place-items-center rounded-[14px] size-[clamp(42px,2.9vw,58px)] border border-border-subtle bg-secondary text-foreground p-0 hover:text-primary hover:-translate-y-0.5 hover:bg-white/38 dark:hover:bg-white/13 max-[760px]:size-[43px] max-[760px]:rounded-xl max-[390px]:size-10',
};

const sizeClasses = {
    default: 'h-11 px-5 py-2.5',
    sm: 'h-9 rounded-lg px-3.5 text-xs',
    lg: 'h-12 px-6 text-base',
    icon: 'size-11 p-0',
    none: '',
};

/**
 * @param {{ variant?: keyof typeof variantClasses, size?: 'default' | 'sm' | 'lg' | 'icon' | 'none' }} options
 * @returns {string}
 */
export function buttonVariants({ variant = 'default', size = 'default' } = {}) {
    return [
        baseClasses,
        variantClasses[variant] ?? variantClasses.default,
        sizeClasses[size] ?? sizeClasses.default,
    ].join(' ');
}
