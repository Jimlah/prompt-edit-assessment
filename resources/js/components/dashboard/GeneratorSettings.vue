<script setup>
import { computed, ref } from 'vue';

import AppIcon from '../AppIcon.vue';

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
    class="settings-panel"
    aria-label="Image generation settings"
    @submit.prevent="emit('generate')"
  >
    <div class="prompt-card">
      <label
        class="sr-only"
        for="image-prompt"
      >Prompt</label>
      <textarea
        id="image-prompt"
        v-model="prompt"
        maxlength="4000"
        rows="8"
        required
        :disabled="isGenerating"
        placeholder="Describe the image you want to create…"
        @keydown.meta.enter.prevent="emit('generate')"
        @keydown.ctrl.enter.prevent="emit('generate')"
      />
      <div class="prompt-footer">
        <span>{{ prompt.length }}/4000</span>
        <button
          type="button"
          class="enhance-button"
          aria-label="Enhance prompt"
          title="Enhance prompt"
          :disabled="isGenerating"
          @click="emit('enhance')"
        >
          <AppIcon
            name="wand"
            :size="21"
          />
        </button>
      </div>
    </div>

    <section
      class="parameter-card"
      aria-labelledby="image-size-label"
    >
      <div class="parameter-heading">
        <span id="image-size-label">
          <AppIcon
            name="crop"
            :size="21"
          />
          Image Size
        </span>
        <small>{{ selectedSize.ratio }}</small>
      </div>
      <div class="segmented-options">
        <button
          v-for="sizeOption in sizes"
          :key="sizeOption.value"
          type="button"
          :class="{ selected: size === sizeOption.value }"
          :aria-pressed="size === sizeOption.value"
          :disabled="isGenerating"
          @click="size = sizeOption.value"
        >
          <span>{{ sizeOption.label }}</span>
          <small>{{ sizeOption.ratio }}</small>
        </button>
      </div>
    </section>

    <section
      class="parameter-card"
      aria-labelledby="image-quality-label"
    >
      <div class="parameter-heading">
        <span id="image-quality-label">
          <AppIcon
            name="detail"
            :size="21"
          />
          Quality
        </span>
      </div>
      <div class="segmented-options">
        <button
          v-for="qualityOption in qualities"
          :key="qualityOption.value"
          type="button"
          :class="{ selected: quality === qualityOption.value }"
          :aria-pressed="quality === qualityOption.value"
          :disabled="isGenerating"
          @click="quality = qualityOption.value"
        >
          <span>{{ qualityOption.label }}</span>
          <small>{{ qualityOption.detail }}</small>
        </button>
      </div>
    </section>

    <section class="references-card">
      <button
        type="button"
        class="references-toggle"
        :aria-expanded="referencesOpen"
        aria-controls="reference-images"
        @click="referencesOpen = !referencesOpen"
      >
        <span>
          <AppIcon
            name="image"
            :size="21"
          />
          Reference Images
        </span>
        <span class="reference-count">{{ referenceImages.length }}/4</span>
        <AppIcon
          name="chevronRight"
          :size="18"
          :style="{ transform: referencesOpen ? 'rotate(90deg)' : 'none' }"
        />
      </button>
      <div
        v-if="referencesOpen"
        id="reference-images"
        class="references-content"
      >
        <p>Optional public image URLs to guide the visual style.</p>
        <div
          v-for="(url, index) in referenceImages"
          :key="index"
          class="reference-input-row"
        >
          <label
            class="sr-only"
            :for="`reference-image-${index}`"
          >Reference image {{ index + 1 }} URL</label>
          <input
            :id="`reference-image-${index}`"
            :value="url"
            type="url"
            inputmode="url"
            placeholder="https://example.com/image.png"
            :disabled="isGenerating"
            @input="updateReference(index, $event.target.value)"
          >
          <button
            type="button"
            :aria-label="`Remove reference image ${index + 1}`"
            :disabled="isGenerating"
            @click="removeReference(index)"
          >
            <AppIcon
              name="close"
              :size="17"
            />
          </button>
        </div>
        <button
          v-if="referenceImages.length < 4"
          type="button"
          class="add-reference-button"
          :disabled="isGenerating"
          @click="addReference"
        >
          <AppIcon
            name="plus"
            :size="17"
          />
          Add reference URL
        </button>
      </div>
    </section>

    <div class="action-area">
      <p
        v-if="estimateLabel"
        class="estimate-note"
      >
        <span
          class="estimate-amounts"
          aria-live="polite"
        >Est. {{ estimateLabel }}</span>
        <small>Price includes the 25% service fee. The final cost is recorded when the image finishes.</small>
      </p>
      <p
        v-if="statusMessage"
        class="message status-message"
        role="status"
      >
        {{ statusMessage }}
      </p>
      <button
        type="submit"
        class="generate-button"
        :disabled="isGenerating || !prompt.trim()"
      >
        <span
          v-if="isGenerating"
          class="spinner"
          aria-hidden="true"
        />
        {{ isGenerating ? 'Queueing…' : 'Generate' }}
      </button>
    </div>
  </form>
</template>

<style scoped>
.settings-panel {
  display: flex;
  min-height: 0;
  flex: 1;
  flex-direction: column;
  gap: clamp(11px, 0.72vw, 15px);
  color: var(--text);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.prompt-card {
  display: flex;
  min-height: clamp(220px, 16.5vw, 315px);
  flex-direction: column;
  padding: clamp(17px, 1.2vw, 24px);
  background: var(--control);
  border: 1px solid var(--subtle-border);
  border-radius: clamp(20px, 1.6vw, 32px);
}

textarea {
  width: 100%;
  min-height: 0;
  flex: 1;
  padding: 0;
  resize: none;
  color: var(--text);
  font: inherit;
  font-size: clamp(15px, 0.92vw, 19px);
  line-height: 1.55;
  background: transparent;
  border: 0;
  outline: 0;
}

textarea::placeholder,
input::placeholder {
  color: var(--muted);
  opacity: 0.7;
}

textarea:disabled {
  opacity: 0.65;
}

.prompt-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 12px;
  color: var(--muted);
  font-size: 13px;
  font-variant-numeric: tabular-nums;
}

.enhance-button {
  display: inline-grid;
  width: 46px;
  height: 46px;
  flex: 0 0 auto;
  place-items: center;
  color: var(--text);
  cursor: pointer;
  background: rgb(255 255 255 / 6%);
  border: 0;
  border-radius: 13px;
  transition: background 160ms ease, transform 160ms ease;
}

.enhance-button:hover {
  background: rgb(255 255 255 / 13%);
  transform: translateY(-1px);
}

.parameter-card,
.references-card {
  padding: clamp(13px, 0.85vw, 18px);
  background: var(--control);
  border: 1px solid var(--subtle-border);
  border-radius: 15px;
}

.references-card {
  padding: 0;
  overflow: hidden;
}

.parameter-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  color: var(--muted);
  font-size: 12px;
}

.parameter-heading > span,
.references-toggle > span:first-child {
  display: inline-flex;
  align-items: center;
  gap: 11px;
  color: var(--text);
  font-size: clamp(14px, 0.83vw, 17px);
}

.parameter-heading > small {
  color: var(--muted);
  font-size: 12px;
}

.segmented-options {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 7px;
  margin-top: 14px;
}

.segmented-options button {
  display: flex;
  min-height: 47px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  font: inherit;
  font-size: 13px;
  cursor: pointer;
  background: rgb(255 255 255 / 5%);
  border: 1px solid transparent;
  border-radius: 11px;
  transition: color 160ms ease, background 160ms ease;
}

.segmented-options button:hover {
  background: rgb(255 255 255 / 10%);
}

.segmented-options button.selected {
  color: #2c290f;
  background: var(--accent);
}

.segmented-options small {
  margin-top: 2px;
  font-size: 10px;
  opacity: 0.72;
}

.references-toggle {
  display: flex;
  width: 100%;
  min-height: clamp(50px, 2.75vw, 56px);
  align-items: center;
  gap: 10px;
  padding: 10px clamp(13px, 0.85vw, 18px);
  color: var(--text);
  font: inherit;
  text-align: left;
  cursor: pointer;
  background: transparent;
  border: 0;
}

.references-toggle > svg {
  transition: transform 180ms ease;
}

.reference-count {
  margin-left: auto;
  color: var(--muted);
  font-size: 12px;
  font-variant-numeric: tabular-nums;
}

.references-content {
  display: flex;
  flex-direction: column;
  gap: 9px;
  padding: 0 clamp(13px, 0.85vw, 18px) clamp(13px, 0.85vw, 18px);
}

.references-content > p {
  margin: 0 0 2px;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.5;
}

.reference-input-row {
  display: flex;
  align-items: center;
  gap: 7px;
}

.reference-input-row input {
  width: 100%;
  min-width: 0;
  min-height: 42px;
  padding: 9px 11px;
  color: var(--text);
  font: inherit;
  font-size: 12px;
  background: rgb(255 255 255 / 5%);
  border: 1px solid var(--subtle-border);
  border-radius: 10px;
  outline: 0;
}

.reference-input-row button {
  display: inline-grid;
  width: 38px;
  height: 38px;
  flex: 0 0 auto;
  place-items: center;
  color: var(--muted);
  cursor: pointer;
  background: rgb(255 255 255 / 5%);
  border: 0;
  border-radius: 10px;
}

.add-reference-button {
  display: inline-flex;
  min-height: 40px;
  align-items: center;
  justify-content: center;
  gap: 7px;
  color: var(--text);
  font: inherit;
  font-size: 12px;
  cursor: pointer;
  background: rgb(255 255 255 / 5%);
  border: 1px dashed var(--border);
  border-radius: 10px;
}

.action-area {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: auto;
}

.estimate-note {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 10px 12px;
  margin: 0;
  background: rgb(255 212 0 / 7%);
  border: 1px solid rgb(255 212 0 / 18%);
  border-radius: 10px;
}

.estimate-amounts {
  color: var(--text);
  font-size: clamp(13px, 0.83vw, 16px);
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

.estimate-note small {
  color: var(--muted);
  font-size: 10.5px;
  line-height: 1.45;
}

.generate-button {
  display: inline-flex;
  width: 100%;
  min-height: clamp(50px, 2.8vw, 57px);
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #2c290f;
  font: inherit;
  font-size: clamp(15px, 0.86vw, 18px);
  font-weight: 600;
  cursor: pointer;
  background: var(--accent);
  border: 0;
  border-radius: 15px;
  box-shadow: 0 12px 28px rgb(255 193 0 / 10%);
  transition: transform 180ms ease, background 180ms ease, opacity 180ms ease;
}

.generate-button:hover:not(:disabled) {
  background: var(--accent-hover);
  transform: translateY(-2px);
}

.generate-button:disabled {
  cursor: not-allowed;
  opacity: 0.58;
}

.spinner {
  width: 17px;
  height: 17px;
  border: 2px solid rgb(44 41 15 / 30%);
  border-top-color: #2c290f;
  border-radius: 50%;
  animation: spin 700ms linear infinite;
}

.message {
  padding: 10px 12px;
  margin: 0;
  font-size: 12px;
  line-height: 1.45;
  border-radius: 10px;
}

.status-message {
  color: var(--text);
  background: rgb(255 255 255 / 5%);
}

button:focus-visible,
input:focus-visible,
textarea:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 3px;
}

textarea:focus-visible {
  border-radius: 4px;
}

button:disabled,
input:disabled {
  cursor: not-allowed;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 760px) {
  .settings-panel {
    flex: none;
  }

  .prompt-card {
    min-height: 220px;
  }

  .parameter-card,
  .references-card {
    border-radius: 17px;
  }

  .action-area {
    margin-top: 5px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .spinner {
    animation-duration: 1.5s;
  }

  .generate-button,
  .enhance-button,
  .segmented-options button,
  .references-toggle > svg {
    transition: none;
  }
}
</style>
