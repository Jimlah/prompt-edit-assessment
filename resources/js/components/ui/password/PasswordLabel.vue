<script setup>
import { FieldLabel } from '@ui-sdk/vue/field';
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
    'w-fit text-sm font-medium text-current data-disabled:opacity-50',
    attrs.class,
));
</script>

<template>
  <FieldLabel
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </FieldLabel>
</template>
