<script setup>
import { MenuItem as UiMenuItem } from '@ui-sdk/vue/menu';
import { computed, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { menuItemVariants } from './menuVariants';

defineOptions({
    inheritAttrs: false,
});

const attrs = useAttrs();

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(menuItemVariants(), attrs.class));
</script>

<template>
  <UiMenuItem
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </UiMenuItem>
</template>
