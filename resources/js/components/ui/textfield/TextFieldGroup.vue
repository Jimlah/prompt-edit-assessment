<script setup>
import { InputGroup } from '@ui-sdk/vue/input';
import { computed, inject, provide, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { textFieldContextKey, textFieldGroupKey } from './context';
import { textFieldGroupVariants } from './textFieldVariants';

defineOptions({
    inheritAttrs: false,
});

const attrs = useAttrs();
const context = inject(textFieldContextKey, null);

provide(textFieldGroupKey, true);

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    textFieldGroupVariants({
        variant: context?.variant.value ?? 'default',
        size: context?.size.value ?? 'default',
    }),
    attrs.class,
));
</script>

<template>
  <InputGroup
    v-bind="forwardedAttrs"
    :class="classes"
  >
    <slot />
  </InputGroup>
</template>
