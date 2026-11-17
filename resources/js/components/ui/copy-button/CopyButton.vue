<script setup lang="ts">
import { createParts } from '@ui-sdk/vue/primitive';
import { computed, useAttrs } from 'vue';

import AppIcon from '../../AppIcon.vue';
import { buttonVariants } from '../button/buttonVariants';
import { copyButtonPrimitive } from './copyButtonPrimitive';
import { cn } from '../../../lib/utils';
import { notify } from '../../../lib/toast';

defineOptions({
    inheritAttrs: false,
});

const { Root: CopyButtonRoot } = createParts(copyButtonPrimitive, { root: 'button' });

const props = withDefaults(defineProps<{
    value: string;
    label?: string;
    copiedLabel?: string;
    successMessage?: string;
    errorMessage?: string;
    timeout?: number;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link' | 'primary' | 'chip' | 'circle' | 'panel' | 'tool';
    size?: 'default' | 'sm' | 'lg' | 'icon' | 'none';
    iconSize?: number;
    showToast?: boolean;
    disabled?: boolean;
}>(), {
    label: 'Copy to clipboard',
    copiedLabel: 'Copied to clipboard',
    successMessage: 'Copied to your clipboard.',
    errorMessage: 'Unable to copy to clipboard.',
    timeout: 2000,
    variant: 'ghost',
    size: 'none',
    iconSize: 14,
    showToast: true,
    disabled: false,
});

const emit = defineEmits<{
    copied: [value: string];
    error: [error: unknown];
}>();

const rawAttrs = useAttrs();

const forwardedAttrs = computed(() => {
    const attributes: Record<string, unknown> = { ...rawAttrs };

    delete attributes.class;

    return attributes;
});

const classes = computed(() => cn(
    'group',
    buttonVariants({
        variant: props.variant,
        size: props.size,
    }),
    rawAttrs.class,
));

function handleCopied(text: string): void {
    if (props.showToast && props.successMessage) {
        notify(props.successMessage);
    }

    emit('copied', text);
}

function handleError(error: unknown): void {
    if (props.showToast && props.errorMessage) {
        notify(props.errorMessage, 'error');
    }

    emit('error', error);
}
</script>

<template>
  <CopyButtonRoot
    v-slot="{ state }"
    v-bind="forwardedAttrs"
    :value="value"
    :timeout="timeout"
    :disabled="disabled"
    :label="label"
    :copied-label="copiedLabel"
    :class="classes"
    @copied="handleCopied"
    @error="handleError"
  >
    <slot
      :copied="state.copied"
      :state="state"
      :disabled="disabled || !value"
    >
      <AppIcon
        name="copy"
        :size="iconSize"
        class="group-data-[copied]:hidden"
      />
      <AppIcon
        name="check"
        :size="iconSize"
        class="hidden text-primary group-data-[copied]:block"
      />
    </slot>
  </CopyButtonRoot>
</template>
