<script setup>
import { Button as UiButton } from '@ui-sdk/vue/button';
import { computed, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { buttonVariants } from './buttonVariants';

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

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    buttonVariants({
        variant: props.variant,
        size: props.size,
    }),
    attrs.class,
));
</script>

<template>
  <UiButton
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </UiButton>
</template>
