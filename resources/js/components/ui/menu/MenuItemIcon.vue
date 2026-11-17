<script setup>
import { MenuItemIcon as UiMenuItemIcon } from '@ui-sdk/vue/menu';
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

const classes = computed(() => cn('shrink-0', attrs.class));
</script>

<template>
  <UiMenuItemIcon
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </UiMenuItemIcon>
</template>
