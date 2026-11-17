<script setup>
import { FieldError } from '@ui-sdk/vue/field';
import { computed, inject, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { textFieldContextKey } from './context';

defineOptions({
    inheritAttrs: false,
});

const attrs = useAttrs();
const context = inject(textFieldContextKey, null);

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    'text-sm font-medium',
    context?.variant.value === 'light' ? 'text-red-700' : null,
    context?.variant.value === 'default' ? 'text-red-300' : null,
    context?.variant.value === 'panel' ? 'text-destructive' : null,
    attrs.class,
));
</script>

<template>
  <FieldError
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </FieldError>
</template>
