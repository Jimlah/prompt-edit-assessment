<script setup>
import { Field } from '@ui-sdk/vue/field';
import { computed, provide, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { textFieldContextKey } from './context';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
    },
    size: {
        type: String,
        default: 'default',
    },
});

const attrs = useAttrs();

provide(textFieldContextKey, {
    variant: computed(() => props.variant),
    size: computed(() => props.size),
});

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    'flex w-full min-w-0 flex-col gap-2',
    props.variant === 'light' ? 'text-neutral-950' : null,
    props.variant === 'default' ? 'text-white' : null,
    attrs.class,
));
</script>

<template>
  <Field
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </Field>
</template>
