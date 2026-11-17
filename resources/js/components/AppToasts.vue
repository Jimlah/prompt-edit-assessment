<script setup>
import {
    Toast,
    ToastClose,
    ToastDescription,
    ToastRegion,
    ToastTitle,
} from '@ui-sdk/vue/toast';
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

import AppIcon from './AppIcon.vue';
import { notify, toaster } from '../lib/toast';

let removeSuccessListener;

onMounted(() => {
    removeSuccessListener = router.on('success', (event) => {
        const flash = event.detail.page.props.flash;

        if (flash?.error) {
            notify(flash.error, 'error');
            return;
        }

        if (flash?.status) {
            notify(flash.status);
        }
    });
});

onBeforeUnmount(() => {
    removeSuccessListener?.();
    toaster.dismiss();
});
</script>

<template>
  <ToastRegion
    :toaster="toaster"
    class="fixed right-6 bottom-6 z-[1000] h-[var(--toast-front-height)] w-[min(380px,calc(100vw-32px))] pointer-events-none max-[760px]:right-4 max-[760px]:bottom-4 max-[760px]:left-4 max-[760px]:w-auto"
    aria-label="Notifications"
  >
    <Toast class="group absolute inset-x-0 bottom-0 flex h-[var(--toast-height)] items-start gap-[11px] p-[14px] pl-4 text-sm leading-[1.45] text-foreground pointer-events-auto bg-popover border border-border rounded-[14px] shadow-[0_18px_55px_rgb(0_0_0/28%)] backdrop-blur-[20px] select-none transition-[translate,scale,opacity] duration-200 [transition-timing-function:ease] [translate:var(--toast-swipe-x,0px)_calc((var(--toast-index)*-12px)+var(--toast-swipe-y,0px))] [scale:calc(1-var(--toast-index)*0.04)] [&[data-expanded]]:[translate:var(--toast-swipe-x,0px)_calc(-1*(var(--toast-offset)+var(--toast-index)*12px))] [&[data-expanded]]:[scale:1] data-[starting-style]:data-[front]:[translate:0_100%] data-[ending-style]:opacity-0 data-[swiping]:transition-none motion-reduce:transition-[opacity] motion-reduce:duration-[120ms]">
      <AppIcon
        name="check"
        :size="19"
        data-show="success"
        class="shrink-0 mt-px text-primary hidden group-data-[type=success]:block"
      />
      <AppIcon
        name="bell"
        :size="19"
        data-show="info"
        class="shrink-0 mt-px text-primary hidden group-data-[type=info]:block"
      />
      <AppIcon
        name="alert"
        :size="19"
        data-show="warning"
        class="shrink-0 mt-px text-primary hidden group-data-[type=warning]:block"
      />
      <AppIcon
        name="alert"
        :size="19"
        data-show="error"
        class="shrink-0 mt-0.5 text-destructive hidden group-data-[type=error]:block"
      />
      <div class="flex min-w-0 flex-1 flex-col gap-0.5">
        <ToastTitle />
        <ToastDescription class="text-xs text-muted-foreground" />
      </div>
      <ToastClose
        class="inline-grid size-[27px] shrink-0 place-items-center -mt-1 -mr-1 text-muted-foreground cursor-pointer bg-transparent border-0 rounded-[7px] hover:text-foreground hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
        aria-label="Dismiss notification"
      >
        <AppIcon
          name="close"
          :size="16"
        />
      </ToastClose>
    </Toast>
  </ToastRegion>
</template>
