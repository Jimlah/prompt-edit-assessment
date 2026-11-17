<script setup>
import { Toggle as UiToggle } from '@ui-sdk/vue/toggle';
import { computed, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { toggleVariants } from './toggleVariants';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    variant: {
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
    toggleVariants({ variant: props.variant }),
    attrs.class,
));
</script>

<template>
  <UiToggle
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </UiToggle>
</template>
