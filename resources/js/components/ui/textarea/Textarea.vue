<script setup>
import { Textarea as UiTextarea } from '@ui-sdk/vue/textarea';
import { computed, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { textareaVariants } from './textareaVariants';

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
    textareaVariants({ variant: props.variant }),
    attrs.class,
));
</script>

<template>
  <UiTextarea
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </UiTextarea>
</template>
