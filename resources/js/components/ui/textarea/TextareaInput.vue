<script setup>
import { Textarea } from '@ui-sdk/vue/textarea';
import { computed, inject, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { textareaContextKey, textareaGroupKey } from './context';
import { textareaVariants } from './textareaVariants';

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
const context = inject(textareaContextKey, null);
const isGrouped = inject(textareaGroupKey, false);

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    textareaVariants({
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
  <Textarea
    v-bind="forwardedAttrs"
    :value="modelValue"
    :class="classes"
    @value-change="onValueChange"
  />
</template>
