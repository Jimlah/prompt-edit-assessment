<script setup>
import { PasswordInputControl } from '@ui-sdk/vue/password-input';
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
    'h-full min-w-0 flex-1 rounded-none border-0 bg-transparent px-0 text-current shadow-none placeholder:text-current/70 focus-visible:outline-none disabled:cursor-not-allowed data-disabled:cursor-not-allowed',
    attrs.class,
));
</script>

<template>
  <PasswordInputControl
    v-bind="forwardedAttrs"
    :class="classes"
  />
</template>
