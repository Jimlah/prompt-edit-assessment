<script setup>
import { computed, inject, provide, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { textareaContextKey, textareaGroupKey } from './context';
import { textareaGroupVariants } from './textareaVariants';

defineOptions({
    inheritAttrs: false,
});

const attrs = useAttrs();
const context = inject(textareaContextKey, null);

provide(textareaGroupKey, true);

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    textareaGroupVariants({
        variant: context?.variant.value ?? 'default',
    }),
    attrs.class,
));
</script>

<template>
  <div
    v-bind="forwardedAttrs"
    data-slot="textarea-group"
    :class="classes"
  >
    <slot />
  </div>
</template>
