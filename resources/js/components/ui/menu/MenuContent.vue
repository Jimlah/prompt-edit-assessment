<script setup>
import { MenuContent as UiMenuContent } from '@ui-sdk/vue/menu';
import { computed, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { menuContentVariants } from './menuVariants';

defineOptions({
    inheritAttrs: false,
});

const attrs = useAttrs();

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(menuContentVariants(), attrs.class));
</script>

<template>
  <UiMenuContent
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </UiMenuContent>
</template>
