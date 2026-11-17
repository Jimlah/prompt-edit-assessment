<script setup>
import { CheckboxIndicator } from '@ui-sdk/vue/checkbox';
import { computed, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';

defineOptions({
    inheritAttrs: false,
});

const attrs = useAttrs();

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    'inline-flex size-full items-center justify-center data-ending-style:scale-50 data-ending-style:opacity-0 data-starting-style:scale-50 data-starting-style:opacity-0 motion-safe:transition-[opacity,scale] motion-safe:duration-150 [&_svg]:size-3.5',
    attrs.class,
));
</script>

<template>
  <CheckboxIndicator
    v-slot="{ state }"
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot :state="state">
      <svg
        v-if="state.indeterminate"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
        stroke-linecap="round"
        aria-hidden="true"
      >
        <path d="M5 12h14" />
      </svg>
      <svg
        v-else
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="m5 12 4 4L19 6" />
      </svg>
    </slot>
  </CheckboxIndicator>
</template>
