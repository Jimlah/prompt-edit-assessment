<script setup>
import { Input } from '@ui-sdk/vue/input';
import { computed, inject, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { textFieldContextKey, textFieldGroupKey } from './context';
import { textFieldVariants } from './textFieldVariants';

defineOptions({
    inheritAttrs: false,
});

defineProps({
    modelValue: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'value-change']);
const attrs = useAttrs();
const context = inject(textFieldContextKey, null);
const isGrouped = inject(textFieldGroupKey, false);

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    textFieldVariants({
        variant: context?.variant.value ?? 'default',
        size: context?.size.value ?? 'default',
        grouped: isGrouped,
    }),
    attrs.class,
));

function onValueChange(value, details) {
    emit('update:modelValue', value);
    emit('value-change', value, details);
}
</script>

<template>
  <Input
    v-bind="forwardedAttrs"
    :value="modelValue"
    :class="classes"
    @value-change="onValueChange"
  />
</template>
