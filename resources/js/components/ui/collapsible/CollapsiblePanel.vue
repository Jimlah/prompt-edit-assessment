<script setup>
import { CollapsiblePanel as UiCollapsiblePanel } from '@ui-sdk/vue/collapsible';
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
    'h-[var(--collapsible-panel-height)] overflow-hidden transition-[height] duration-200 ease-out',
    'data-starting-style:h-0 data-ending-style:h-0',
    attrs.class,
));
</script>

<template>
  <UiCollapsiblePanel
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </UiCollapsiblePanel>
</template>
