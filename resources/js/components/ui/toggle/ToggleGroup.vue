<script setup>
import { ToggleGroup as UiToggleGroup } from '@ui-sdk/vue/toggle';
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

const classes = computed(() => cn('flex items-stretch', attrs.class));
</script>

<template>
  <UiToggleGroup
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </UiToggleGroup>
</template>
