const contentClasses = [
    'z-50 w-[230px] p-[9px] text-foreground bg-popover border border-border rounded-2xl backdrop-blur-[24px]',
    'shadow-[0_20px_55px_rgb(0_0_0/30%)] origin-[var(--transform-origin)]',
    'transition-[opacity,scale] duration-150 ease-out motion-reduce:transition-none',
    'data-starting-style:opacity-0 data-starting-style:scale-95 data-ending-style:opacity-0 data-ending-style:scale-95',
].join(' ');

const itemBaseClasses = [
    'flex w-full items-center gap-[9px] p-[11px] text-sm text-foreground select-none cursor-pointer',
    'outline-none bg-transparent border-0 rounded-[10px] text-left',
    'data-highlighted:bg-secondary data-disabled:opacity-50 aria-disabled:opacity-50',
    'data-pending:opacity-75 data-pending:cursor-wait',
].join(' ');

/**
 * Popup panel styling for menu content.
 *
 * @returns {string}
 */
export function menuContentVariants() {
    return contentClasses;
}

/**
 * Row styling for a menu item.
 *
 * @returns {string}
 */
export function menuItemVariants() {
    return itemBaseClasses;
}
