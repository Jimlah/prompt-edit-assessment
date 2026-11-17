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
    class="app-toast-region"
    aria-label="Notifications"
  >
    <Toast class="app-toast">
      <AppIcon
        name="check"
        :size="19"
        data-show="success"
      />
      <AppIcon
        name="bell"
        :size="19"
        data-show="info"
      />
      <AppIcon
        name="alert"
        :size="19"
        data-show="warning"
      />
      <AppIcon
        name="alert"
        :size="19"
        data-show="error"
      />
      <div class="app-toast-copy">
        <ToastTitle />
        <ToastDescription />
      </div>
      <ToastClose
        class="app-toast-close"
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

<style>
.app-toast-region {
  position: fixed;
  z-index: 1000;
  right: 24px;
  bottom: 24px;
  width: min(380px, calc(100vw - 32px));
  height: var(--toast-front-height);
  pointer-events: none;
}

.app-toast {
  position: absolute;
  inset-inline: 0;
  bottom: 0;
  display: flex;
  height: var(--toast-height);
  align-items: flex-start;
  gap: 11px;
  padding: 14px 14px 14px 16px;
  color: #f3f4e9;
  font-size: 14px;
  line-height: 1.45;
  pointer-events: auto;
  background: rgb(41 49 37 / 96%);
  border: 1px solid rgb(255 255 255 / 14%);
  border-radius: 14px;
  box-shadow: 0 18px 55px rgb(0 0 0 / 28%);
  translate: var(--toast-swipe-x, 0px) calc((var(--toast-index) * -12px) + var(--toast-swipe-y, 0px));
  scale: calc(1 - var(--toast-index) * 0.04);
  transition: translate 200ms ease, scale 200ms ease, opacity 200ms ease;
  user-select: none;
  backdrop-filter: blur(20px);
}

.app-toast[data-expanded] {
  translate: var(--toast-swipe-x, 0px) calc(-1 * (var(--toast-offset) + var(--toast-index) * 12px));
  scale: 1;
}

.app-toast[data-starting-style][data-front] {
  translate: 0 100%;
}

.app-toast[data-ending-style] {
  opacity: 0;
}

.app-toast[data-swiping] {
  transition: none;
}

.app-toast > svg {
  flex: 0 0 auto;
  margin-top: 1px;
  color: #ffd400;
}

/* One icon per toast type: hide the others. */
.app-toast > svg[data-show] {
  display: none;
}

.app-toast[data-type="success"] > svg[data-show="success"],
.app-toast[data-type="info"] > svg[data-show="info"],
.app-toast[data-type="warning"] > svg[data-show="warning"],
.app-toast[data-type="error"] > svg[data-show="error"] {
  display: block;
}

.app-toast[data-type="error"] > svg {
  color: #ffab91;
}

.app-toast-copy {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
  gap: 2px;
}

.app-toast-copy [data-slot="toast-description"] {
  color: #c0c3b5;
  font-size: 12px;
}

.app-toast-close {
  display: inline-grid;
  width: 27px;
  height: 27px;
  flex: 0 0 auto;
  place-items: center;
  margin: -4px -4px 0 0;
  color: #c0c3b5;
  cursor: pointer;
  background: transparent;
  border: 0;
  border-radius: 7px;
}

.app-toast-close:hover {
  color: #fff;
  background: rgb(255 255 255 / 8%);
}

.app-toast-close:focus-visible {
  outline: 2px solid #ffd400;
  outline-offset: 2px;
}

@media (max-width: 760px) {
  .app-toast-region {
    right: 16px;
    bottom: 16px;
    left: 16px;
    width: auto;
  }
}

@media (prefers-reduced-motion: reduce) {
  .app-toast {
    transition: opacity 120ms ease;
  }
}
</style>
