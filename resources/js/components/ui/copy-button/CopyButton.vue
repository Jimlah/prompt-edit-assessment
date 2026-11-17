<script setup>
import { attrs as partAttrs } from '@ui-sdk/core/renderer';
import { computed, onBeforeUnmount, ref, useAttrs, useId, watchEffect } from 'vue';

import AppIcon from '../../AppIcon.vue';
import { buttonVariants } from '../button/buttonVariants';
import { copyButtonPrimitive } from './copyButtonPrimitive';
import { cn } from '../../../lib/utils';
import { notify } from '../../../lib/toast';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    value: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: 'Copy to clipboard',
    },
    copiedLabel: {
        type: String,
        default: 'Copied to clipboard',
    },
    successMessage: {
        type: String,
        default: 'Copied to your clipboard.',
    },
    errorMessage: {
        type: String,
        default: 'Unable to copy to clipboard.',
    },
    timeout: {
        type: Number,
        default: 2000,
    },
    variant: {
        type: String,
        default: 'ghost',
    },
    size: {
        type: String,
        default: 'none',
    },
    iconSize: {
        type: Number,
        default: 14,
    },
    showToast: {
        type: Boolean,
        default: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['copied', 'error']);
const rawAttrs = useAttrs();
const id = useId();

const instanceOptions = () => ({
    id,
    value: props.value,
    timeout: props.timeout,
    disabled: props.disabled,
    label: props.label,
    copiedLabel: props.copiedLabel,
    onCopied: (text) => {
        if (props.showToast && props.successMessage) {
            notify(props.successMessage);
        }

        emit('copied', text);
    },
    onError: (error) => {
        if (props.showToast && props.errorMessage) {
            notify(props.errorMessage, 'error');
        }

        emit('error', error);
    },
});

const instance = copyButtonPrimitive(instanceOptions());
const version = ref(0);
const unsubscribe = instance.subscribe(() => {
    version.value += 1;
});

watchEffect(() => {
    instance.setOptions(instanceOptions());
});

onBeforeUnmount(() => {
    unsubscribe();
    instance.destroy();
});

const copied = computed(() => {
    void version.value;

    return instance.get().copied;
});

const rootAttrs = computed(() => {
    void version.value;
    void props.value;
    void props.disabled;
    void props.label;
    void props.copiedLabel;

    return partAttrs(instance.part('root'));
});

const forwardedAttrs = computed(() => {
    const attributes = { ...rawAttrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    buttonVariants({
        variant: props.variant,
        size: props.size,
    }),
    rawAttrs.class,
));
</script>

<template>
  <button
    v-bind="{ ...forwardedAttrs, ...rootAttrs }"
    :class="classes"
    @click="instance.api.copy()"
  >
    <slot :copied="copied">
      <AppIcon
        :name="copied ? 'check' : 'copy'"
        :size="iconSize"
        :class="copied ? 'text-primary' : ''"
      />
    </slot>
  </button>
</template>
