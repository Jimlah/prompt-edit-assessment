<script setup>
import { computed, ref } from 'vue';

import AppIcon from '../AppIcon.vue';
import { Button } from '../ui/button';
import { Collapsible, CollapsiblePanel, CollapsibleTrigger } from '../ui/collapsible';
import { TextField } from '../ui/textfield';
import { Toggle, ToggleGroup } from '../ui/toggle';
import { Textarea } from '../ui/textarea';

defineProps({
    isGenerating: {
        type: Boolean,
        default: false,
    },
    statusMessage: {
        type: String,
        default: '',
    },
    estimateLabel: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['generate', 'enhance']);

const prompt = defineModel('prompt', { type: String, required: true });
const quality = defineModel('quality', { type: String, required: true });
const size = defineModel('size', { type: String, required: true });
const referenceImages = defineModel('referenceImages', { type: Array, required: true });

const sizes = [
    { value: '1024x1024', label: 'Square', ratio: '1:1' },
    { value: '1536x1024', label: 'Wide', ratio: '3:2' },
    { value: '1024x1536', label: 'Tall', ratio: '2:3' },
];
const qualities = [
    { value: 'low', label: 'Low', detail: 'Faster' },
    { value: 'medium', label: 'Medium', detail: 'Balanced' },
    { value: 'high', label: 'High', detail: 'Detailed' },
];

const referencesOpen = ref(false);
const selectedSize = computed(() => sizes.find((option) => option.value === size.value) ?? sizes[0]);

function selectSize(value) {
    if (value.length > 0) {
        size.value = value[value.length - 1];
    }
}

function selectQuality(value) {
    if (value.length > 0) {
        quality.value = value[value.length - 1];
    }
}

function addReference() {
    if (referenceImages.value.length < 4) {
        referenceImages.value = [...referenceImages.value, ''];
    }
}

function updateReference(index, value) {
    const updated = [...referenceImages.value];
    updated[index] = value;
    referenceImages.value = updated;
}

function removeReference(index) {
    referenceImages.value = referenceImages.value.filter((_, itemIndex) => itemIndex !== index);
}
</script>

<template>
  <form
    class="flex min-h-0 flex-1 flex-col gap-[clamp(11px,0.72vw,15px)] text-foreground max-[760px]:flex-none"
    aria-label="Image generation settings"
    @submit.prevent="emit('generate')"
  >
    <div class="flex min-h-[clamp(220px,16.5vw,315px)] flex-col p-[clamp(17px,1.2vw,24px)] bg-secondary border border-border-subtle rounded-[clamp(20px,1.6vw,32px)] max-[760px]:min-h-[220px] max-[760px]:rounded-[17px]">
      <label
        class="sr-only"
        for="image-prompt"
      >Prompt</label>
      <Textarea
        id="image-prompt"
        v-model:value="prompt"
        maxlength="4000"
        rows="8"
        required
        :disabled="isGenerating"
        submit-shortcut="mod+enter"
        placeholder="Describe the image you want to create…"
        class="w-full min-h-0 flex-1 text-[clamp(15px,0.92vw,19px)]"
      />
      <div class="flex items-center justify-between pt-3 text-muted-foreground text-[13px] tabular-nums">
        <span>{{ prompt.length }}/4000</span>
        <Button
          variant="panel"
          size="none"
          class="size-[46px] rounded-[13px] border-0 bg-white/6 hover:bg-white/13 hover:-translate-y-px"
          aria-label="Enhance prompt"
          title="Enhance prompt"
          :disabled="isGenerating"
          @click="emit('enhance')"
        >
          <AppIcon
            name="wand"
            :size="21"
          />
        </Button>
      </div>
    </div>

    <section
      class="p-[clamp(13px,0.85vw,18px)] bg-secondary border border-border-subtle rounded-[15px] max-[760px]:rounded-[17px]"
      aria-labelledby="image-size-label"
    >
      <div class="flex items-center justify-between gap-3 text-muted-foreground text-xs">
        <span
          id="image-size-label"
          class="inline-flex items-center gap-[11px] text-foreground text-[clamp(14px,0.83vw,17px)]"
        >
          <AppIcon
            name="crop"
            :size="21"
          />
          Image Size
        </span>
        <small class="text-muted-foreground text-xs">{{ selectedSize.ratio }}</small>
      </div>
      <ToggleGroup
        class="grid grid-cols-[repeat(3,minmax(0,1fr))] gap-[7px] mt-[14px]"
        :value="[size]"
        :deselectable="false"
        :disabled="isGenerating"
        aria-labelledby="image-size-label"
        @value-change="selectSize"
      >
        <Toggle
          v-for="sizeOption in sizes"
          :key="sizeOption.value"
          :value="sizeOption.value"
        >
          <span>{{ sizeOption.label }}</span>
          <small class="mt-0.5 text-[10px] opacity-72">{{ sizeOption.ratio }}</small>
        </Toggle>
      </ToggleGroup>
    </section>

    <section
      class="p-[clamp(13px,0.85vw,18px)] bg-secondary border border-border-subtle rounded-[15px] max-[760px]:rounded-[17px]"
      aria-labelledby="image-quality-label"
    >
      <div class="flex items-center justify-between gap-3 text-muted-foreground text-xs">
        <span
          id="image-quality-label"
          class="inline-flex items-center gap-[11px] text-foreground text-[clamp(14px,0.83vw,17px)]"
        >
          <AppIcon
            name="detail"
            :size="21"
          />
          Quality
        </span>
      </div>
      <ToggleGroup
        class="grid grid-cols-[repeat(3,minmax(0,1fr))] gap-[7px] mt-[14px]"
        :value="[quality]"
        :deselectable="false"
        :disabled="isGenerating"
        aria-labelledby="image-quality-label"
        @value-change="selectQuality"
      >
        <Toggle
          v-for="qualityOption in qualities"
          :key="qualityOption.value"
          :value="qualityOption.value"
        >
          <span>{{ qualityOption.label }}</span>
          <small class="mt-0.5 text-[10px] opacity-72">{{ qualityOption.detail }}</small>
        </Toggle>
      </ToggleGroup>
    </section>

    <Collapsible
      v-model:open="referencesOpen"
      class="overflow-hidden bg-secondary border border-border-subtle rounded-[15px] max-[760px]:rounded-[17px]"
    >
      <CollapsibleTrigger class="flex w-full min-h-[clamp(50px,2.75vw,56px)] items-center gap-2.5 py-2.5 px-[clamp(13px,0.85vw,18px)] text-foreground text-left">
        <span class="inline-flex items-center gap-[11px] text-foreground text-[clamp(14px,0.83vw,17px)]">
          <AppIcon
            name="image"
            :size="21"
          />
          Reference Images
        </span>
        <span class="ml-auto text-muted-foreground text-xs tabular-nums">{{ referenceImages.length }}/4</span>
        <AppIcon
          name="chevronRight"
          :size="18"
          class="transition-transform duration-[180ms] ease-[ease] motion-reduce:transition-none group-data-open:rotate-90"
        />
      </CollapsibleTrigger>
      <CollapsiblePanel class="flex flex-col gap-[9px] pb-[clamp(13px,0.85vw,18px)] px-[clamp(13px,0.85vw,18px)]">
        <p class="m-0 mb-0.5 text-muted-foreground text-[11px] leading-[1.5]">Optional public image URLs to guide the visual style.</p>
        <div
          v-for="(url, index) in referenceImages"
          :key="index"
          class="flex items-center gap-[7px]"
        >
          <TextField
            class="flex-1"
            variant="panel"
            size="none"
          >
            <TextField.Label class="sr-only">
              Reference image {{ index + 1 }} URL
            </TextField.Label>
            <TextField.Input
              :model-value="url"
              type="url"
              inputmode="url"
              placeholder="https://example.com/image.png"
              :disabled="isGenerating"
              @value-change="updateReference(index, $event)"
            />
          </TextField>
          <Button
            variant="panel"
            size="none"
            class="size-[38px] shrink-0 rounded-[10px] border-0 text-muted-foreground"
            :aria-label="`Remove reference image ${index + 1}`"
            :disabled="isGenerating"
            @click="removeReference(index)"
          >
            <AppIcon
              name="close"
              :size="17"
            />
          </Button>
        </div>
        <Button
          v-if="referenceImages.length < 4"
          variant="panel"
          size="none"
          class="inline-flex w-full min-h-10 items-center justify-center gap-[7px] rounded-[10px] border-dashed border-border text-xs font-normal hover:bg-foreground/10"
          :disabled="isGenerating"
          @click="addReference"
        >
          <AppIcon
            name="plus"
            :size="17"
          />
          Add reference URL
        </Button>
      </CollapsiblePanel>
    </Collapsible>

    <div class="flex flex-col gap-2 mt-auto max-[760px]:mt-[5px]">
      <p
        v-if="estimateLabel"
        class="flex flex-col gap-[3px] py-2.5 px-3 m-0 bg-primary/7 border border-primary/18 rounded-[10px]"
      >
        <span
          aria-live="polite"
          class="text-foreground text-[clamp(13px,0.83vw,16px)] font-semibold tabular-nums"
        >Est. {{ estimateLabel }}</span>
        <small class="text-muted-foreground text-[10.5px] leading-[1.45]">Price includes the 25% service fee. The final cost is recorded when the image finishes.</small>
      </p>
      <p
        v-if="statusMessage"
        role="status"
        class="py-2.5 px-3 m-0 text-xs leading-[1.45] rounded-[10px] text-foreground bg-white/5"
      >
        {{ statusMessage }}
      </p>
      <Button
        type="submit"
        variant="primary"
        size="none"
        class="inline-flex w-full min-h-[clamp(50px,2.8vw,57px)] items-center justify-center gap-2.5 rounded-[15px] text-[clamp(15px,0.86vw,18px)]"
        :disabled="isGenerating || !prompt.trim()"
        focusable-when-disabled
      >
        <span
          v-if="isGenerating"
          aria-hidden="true"
          class="size-[17px] rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground animate-[spin_700ms_linear_infinite] motion-reduce:[animation-duration:1.5s]"
        />
        {{ isGenerating ? 'Queueing…' : 'Generate' }}
      </Button>
    </div>
  </form>
</template>
