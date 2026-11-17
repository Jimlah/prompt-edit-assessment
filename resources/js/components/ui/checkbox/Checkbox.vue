<script setup>
import { Checkbox as UiCheckbox } from '@ui-sdk/vue/checkbox';
import { computed, useAttrs } from 'vue';

import { cn } from '../../../lib/utils';
import { checkboxVariants } from './checkboxVariants';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    checked: {
        type: [Boolean, String],
        default: undefined,
    },
    variant: {
        type: String,
        default: 'default',
    },
});

const emit = defineEmits(['update:checked', 'checked-change']);
const attrs = useAttrs();

const forwardedAttrs = computed(() => {
    const attributes = { ...attrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    checkboxVariants({ variant: props.variant }),
    attrs.class,
));

function onCheckedChange(checked, details) {
    emit('update:checked', checked);
    emit('checked-change', checked, details);
}
</script>

<template>
  <UiCheckbox
    v-bind="forwardedAttrs"
    :checked="checked"
    :class="classes"
    @checked-change="onCheckedChange"
  >
    <slot />
  </UiCheckbox>
</template>
