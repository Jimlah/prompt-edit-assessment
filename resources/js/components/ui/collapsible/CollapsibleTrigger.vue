<script setup>
import { CollapsibleTrigger as UiCollapsibleTrigger } from '@ui-sdk/vue/collapsible';
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

const classes = computed(() => cn('group', attrs.class));
</script>

<template>
  <UiCollapsibleTrigger
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </UiCollapsibleTrigger>
</template>
