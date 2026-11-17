<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppIcon from '../AppIcon.vue';
import { Button } from '../ui/button';
import { Collapsible, CollapsiblePanel, CollapsibleTrigger } from '../ui/collapsible';
import { TextField } from '../ui/textfield';
import { Toggle, ToggleGroup } from '../ui/toggle';
import { Textarea } from '../ui/textarea';
import { computeEstimateNd, formatRangeNd } from '../../lib/pricing';
import { notify } from '../../lib/toast';

const props = defineProps({
    initialPrompt: {
        type: String,
        default: '',
    },
});

const defaultPrompt = 'A luminous world in miniature: a cyberpunk android portrait, an ornate mechanical beetle on a mossy stone, and a glass lantern filled with glowing butterflies. Intricate details, rich color, cinematic lighting.';

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

const page = usePage();
const pricing = computed(() => page.props.pricing);

const form = useForm({
    prompt: props.initialPrompt || defaultPrompt,
    size: '1024x1024',
    quality: 'high',
    reference_images: [],
});

const selectedSize = computed(() => sizes.find((option) => option.value === form.size) ?? sizes[0]);
const sizeValue = computed(() => [form.size]);
const qualityValue = computed(() => [form.quality]);

const estimate = computed(() => computeEstimateNd(pricing.value, {
    prompt: form.prompt.trim(),
    size: form.size,
    quality: form.quality,
    referenceImages: form.reference_images,
}));

const estimateLabel = computed(() => formatRangeNd(estimate.value?.min_nd ?? null, estimate.value?.max_nd ?? null) ?? '');
const firstError = computed(() => Object.values(form.errors)[0] || '');

function selectSize(value) {
    if (value.length > 0) {
        form.size = value[value.length - 1];
    }
}

function selectQuality(value) {
    if (value.length > 0) {
        form.quality = value[value.length - 1];
    }
}

function addReference() {
    if (form.reference_images.length < 4) {
        form.reference_images = [...form.reference_images, ''];
    }
}

function updateReference(index, value) {
    const updated = [...form.reference_images];
    updated[index] = value;
    form.reference_images = updated;
}

function removeReference(index) {
    form.reference_images = form.reference_images.filter((_, itemIndex) => itemIndex !== index);
}

function enhancePrompt() {
    const enhancement = 'intricate details, balanced composition, cinematic lighting';

    if (!form.prompt.toLowerCase().includes(enhancement)) {
        form.prompt = `${form.prompt.trim()}, ${enhancement}`.trim().slice(0, 4000);
    }

    notify('Finishing touches have been added to your prompt.');
}

function submit() {
    if (form.processing) {
        return;
    }

    if (!form.prompt.trim()) {
        notify('Describe your image before generating.', 'error');
        document.querySelector('#image-prompt')?.focus();
        return;
    }

    form
        .transform((data) => ({
            ...data,
            prompt: data.prompt.trim(),
            reference_images: data.reference_images.map((url) => url.trim()).filter(Boolean),
        }))
        .post('/dashboard/images', {
            preserveState: true,
            preserveScroll: true,
            onStart: () => {
                form.clearErrors();
            },
            onSuccess: () => {
                if (!page.props.flash?.status && !page.props.flash?.error) {
                    notify('Your image has been queued.');
                }
            },
            onError: (errors) => {
                const message = Object.values(errors)[0] || 'Your settings could not be validated.';
                notify(message, 'error');
            },
        });
}
</script>

<template>
  <form
    class="flex min-h-0 flex-1 flex-col gap-[clamp(11px,0.72vw,15px)] text-foreground max-[760px]:flex-none"
    aria-label="Image generation settings"
    @submit.prevent="submit"
  >
    <div class="flex min-h-[clamp(150px,12.5vw,240px)] flex-1 flex-col p-[clamp(15px,1.1vw,22px)] bg-secondary border border-border-subtle rounded-[clamp(20px,1.6vw,32px)] max-[760px]:min-h-[190px] max-[760px]:flex-none max-[760px]:rounded-[17px]">
      <label
        class="sr-only"
        for="image-prompt"
      >Prompt</label>
      <Textarea
        id="image-prompt"
        v-model:value="form.prompt"
        maxlength="4000"
        rows="5"
        required
        :disabled="form.processing"
        submit-shortcut="mod+enter"
        placeholder="Describe the image you want to create…"
        class="w-full min-h-0 flex-1 text-[clamp(15px,0.92vw,19px)]"
      />
      <div class="flex items-center justify-between pt-3 text-muted-foreground text-[13px] tabular-nums">
        <span>{{ form.prompt.length }}/4000</span>
        <div class="flex items-center gap-1.5">
          <Button
            type="button"
            variant="panel"
            size="none"
            class="size-[42px] rounded-[13px] border-0 bg-white/6 hover:bg-white/13 hover:-translate-y-px"
            aria-label="Clear prompt"
            title="Clear prompt"
            :disabled="form.processing || !form.prompt"
            @click="form.prompt = ''"
          >
            <AppIcon
              name="eraser"
              :size="19"
            />
          </Button>
          <Button
            type="button"
            variant="panel"
            size="none"
            class="size-[42px] rounded-[13px] border-0 bg-white/6 hover:bg-white/13 hover:-translate-y-px"
            aria-label="Enhance prompt"
            title="Enhance prompt"
            :disabled="form.processing"
            @click="enhancePrompt"
          >
            <AppIcon
              name="wand"
              :size="20"
            />
          </Button>
        </div>
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
        :value="sizeValue"
        :deselectable="false"
        :disabled="form.processing"
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
        :value="qualityValue"
        :deselectable="false"
        :disabled="form.processing"
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

    <Collapsible class="overflow-hidden bg-secondary border border-border-subtle rounded-[15px] max-[760px]:rounded-[17px]">
      <CollapsibleTrigger class="flex w-full min-h-[clamp(50px,2.75vw,56px)] items-center gap-2.5 py-2.5 px-[clamp(13px,0.85vw,18px)] text-foreground text-left">
        <span class="inline-flex items-center gap-[11px] text-foreground text-[clamp(14px,0.83vw,17px)]">
          <AppIcon
            name="image"
            :size="21"
          />
          Reference Images
        </span>
        <span class="ml-auto text-muted-foreground text-xs tabular-nums">{{ form.reference_images.length }}/4</span>
        <AppIcon
          name="chevronRight"
          :size="18"
          class="transition-transform duration-[180ms] ease-[ease] motion-reduce:transition-none group-data-open:rotate-90"
        />
      </CollapsibleTrigger>
      <CollapsiblePanel class="flex flex-col gap-[9px] pb-[clamp(13px,0.85vw,18px)] px-[clamp(13px,0.85vw,18px)]">
        <p class="m-0 mb-0.5 text-muted-foreground text-[11px] leading-[1.5]">
          Optional public image URLs to guide the visual style.
        </p>
        <div
          v-for="(url, index) in form.reference_images"
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
              :disabled="form.processing"
              @value-change="updateReference(index, $event)"
            />
          </TextField>
          <Button
            type="button"
            variant="panel"
            size="none"
            class="size-[38px] shrink-0 rounded-[10px] border-0 text-muted-foreground"
            :aria-label="`Remove reference image ${index + 1}`"
            :disabled="form.processing"
            @click="removeReference(index)"
          >
            <AppIcon
              name="close"
              :size="17"
            />
          </Button>
        </div>
        <Button
          v-if="form.reference_images.length < 4"
          type="button"
          variant="panel"
          size="none"
          class="inline-flex w-full min-h-10 items-center justify-center gap-[7px] rounded-[10px] border-dashed border-border text-xs font-normal hover:bg-foreground/10"
          :disabled="form.processing"
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
        v-if="firstError"
        role="alert"
        class="py-2.5 px-3 m-0 text-xs leading-[1.45] rounded-[10px] text-destructive bg-destructive/10 border border-destructive/20"
      >
        {{ firstError }}
      </p>
      <Button
        type="submit"
        variant="primary"
        size="none"
        class="inline-flex w-full min-h-[clamp(46px,2.7vw,54px)] items-center justify-center gap-2.5 rounded-[15px] text-[clamp(15px,0.86vw,18px)]"
        :disabled="form.processing || !form.prompt.trim()"
        focusable-when-disabled
      >
        <span
          v-if="form.processing"
          aria-hidden="true"
          class="size-[17px] rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground animate-[spin_700ms_linear_infinite] motion-reduce:[animation-duration:1.5s]"
        />
        {{ form.processing ? 'Queueing…' : 'Generate' }}
      </Button>
    </div>
  </form>
</template>
