<script setup>
import { PasswordInputToggle } from '@ui-sdk/vue/password-input';
import { computed, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';

defineOptions({
    inheritAttrs: false,
});

const attrs = useAttrs();

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return {
        'aria-label': 'Show password',
        ...attributes,
    };
});

const classes = computed(() => cn(
    'inline-flex size-10 shrink-0 items-center justify-center rounded-full text-current/80 transition-colors hover:bg-current/10 focus-visible:outline-2 focus-visible:outline-current data-disabled:opacity-50 [&_svg]:size-4',
    attrs.class,
));
</script>

<template>
  <PasswordInputToggle
    v-slot="{ state }"
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot :state="state">
      <svg
        v-if="state.visible"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="m3 3 18 18" />
        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
        <path d="M9.9 4.2A10.7 10.7 0 0 1 12 4c7 0 10 8 10 8a17 17 0 0 1-2.1 3.2M6.6 6.6C3.1 8.8 2 12 2 12s3 8 10 8a10.8 10.8 0 0 0 5.4-1.5" />
      </svg>
      <svg
        v-else
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="M2 12s3-8 10-8 10 8 10 8-3 8-10 8S2 12 2 12Z" />
        <circle
          cx="12"
          cy="12"
          r="3"
        />
      </svg>
    </slot>
  </PasswordInputToggle>
</template>
