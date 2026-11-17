<script setup>
import { Collapsible as UiCollapsible } from '@ui-sdk/vue/collapsible';
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

const classes = computed(() => cn('group/collapsible overflow-hidden', attrs.class));
</script>

<template>
  <UiCollapsible
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </UiCollapsible>
</template>
