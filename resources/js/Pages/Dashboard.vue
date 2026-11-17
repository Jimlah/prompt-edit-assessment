<script setup>
import { Head, router, useForm, usePage, usePoll } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

import AppIcon from '../components/AppIcon.vue';
import AppToasts from '../components/AppToasts.vue';
import DashboardSidebar from '../components/dashboard/DashboardSidebar.vue';
import GeneratorSettings from '../components/dashboard/GeneratorSettings.vue';
import { notify } from '../lib/toast';
import { computeEstimateNd, formatRangeNd } from '../lib/pricing';

const props = defineProps({
    generations: {
        type: Array,
        default: () => [],
    },
});

const showcase = [
    {
        id: 'showcase-cyberpunk',
        status: 'succeeded',
        prompt: 'Cyberpunk android portrait with luminous circuitry and a neon city bokeh.',
        image_url: '/images/showcase/cyberpunk.webp',
    },
    {
        id: 'showcase-beetle',
        status: 'succeeded',
        prompt: 'An ornate mechanical beetle resting on a moss-covered stone, macro 3D render.',
        image_url: '/images/showcase/beetle.webp',
    },
    {
        id: 'showcase-lantern',
        status: 'succeeded',
        prompt: 'A glass lantern filled with a glowing tree and cyan butterflies in a moonlit forest.',
        image_url: '/images/showcase/lantern.webp',
    },
];

const defaultPrompt = 'A luminous world in miniature: a cyberpunk android portrait, an ornate mechanical beetle on a mossy stone, and a glass lantern filled with glowing butterflies. Intricate details, rich color, cinematic lighting.';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userInitial = computed(() => user.value?.name?.trim()?.charAt(0)?.toUpperCase() || 'I');
const prompt = ref(props.generations[0]?.prompt || defaultPrompt);
const quality = ref('high');
const referenceImages = ref([]);
const size = ref('1024x1024');
const search = ref('');
const displayMode = ref('auto');
const statusMessage = ref('');
const mobileOpen = ref(false);
const settingsOpen = ref(false);
const profileOpen = ref(false);
const lightMode = ref(false);
const balanceDisplay = computed(() => page.props.balance_display ?? null);
const pricing = computed(() => page.props.pricing);
const form = useForm({
    prompt: '',
    size: '1024x1024',
    quality: 'high',
    reference_images: [],
});
const isGenerating = computed(() => form.processing);
const gallery = computed(() => displayMode.value === 'showcase' || props.generations.length === 0 ? showcase : props.generations);
const hasPendingGenerations = computed(() => props.generations.some((generation) => ['queued', 'processing'].includes(generation.status)));
const { start, stop } = usePoll(2000, () => ({
    only: ['generations', 'balance_nd', 'balance_display'],
}), {
    autoStart: false,
    mode: 'rest',
});

const estimate = computed(() => computeEstimateNd(pricing.value, {
    prompt: prompt.value.trim(),
    size: size.value,
    quality: quality.value,
    referenceImages: referenceImages.value,
}));
const estimateLabel = computed(() => formatRangeNd(estimate.value?.min_nd ?? null, estimate.value?.max_nd ?? null) ?? '');

const filteredGenerations = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return gallery.value;
    }

    return gallery.value.filter((generation) => generation.prompt?.toLowerCase().includes(query));
});

watch(hasPendingGenerations, (pending, wasPending) => {
    if (pending) {
        start();
        return;
    }

    stop();

    if (wasPending) {
        statusMessage.value = 'Your generation results have been updated.';
        notify('Your generation results are ready.');
    }
}, { immediate: true });

function focusPrompt() {
    settingsOpen.value = true;
    nextTick(() => document.querySelector('#image-prompt')?.focus());
}

function openSettings() {
    mobileOpen.value = false;
    settingsOpen.value = !settingsOpen.value;

    if (window.innerWidth > 760) {
        focusPrompt();
        return;
    }

    nextTick(() => document.querySelector('#generation-settings')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

function enhancePrompt() {
    const enhancement = 'intricate details, balanced composition, cinematic lighting';

    if (!prompt.value.toLowerCase().includes(enhancement)) {
        prompt.value = `${prompt.value.trim()}, ${enhancement}`.trim().slice(0, 4000);
    }

    notify('Finishing touches have been added to your prompt.');
}

function resetSettings() {
    prompt.value = defaultPrompt;
    quality.value = 'high';
    referenceImages.value = [];
    size.value = '1024x1024';
    displayMode.value = 'showcase';
    statusMessage.value = '';
    notify('Generator settings and showcase restored.');
}

async function copyPrompt() {
    try {
        await navigator.clipboard.writeText(prompt.value);
        notify('Prompt copied to your clipboard.');
    } catch {
        focusPrompt();
        notify('Select and copy the prompt from the settings panel.');
    }
}

function cycleSize() {
    const sizes = ['1024x1024', '1536x1024', '1024x1536'];
    size.value = sizes[(sizes.indexOf(size.value) + 1) % sizes.length];
    notify(`Canvas changed to ${size.value.replace('x', ' × ')}.`);
}

function cycleQuality() {
    const qualities = ['low', 'medium', 'high'];
    quality.value = qualities[(qualities.indexOf(quality.value) + 1) % qualities.length];
    notify(`${quality.value.charAt(0).toUpperCase() + quality.value.slice(1)} quality selected.`);
}

function generateImages() {
    if (form.processing) {
        return;
    }

    if (!prompt.value.trim()) {
        notify('Describe your image before generating.', 'error');
        focusPrompt();
        return;
    }

    form
        .transform(() => ({
            prompt: prompt.value.trim(),
            size: size.value,
            quality: quality.value,
            reference_images: referenceImages.value.map((url) => url.trim()).filter(Boolean),
        }))
        .post('/dashboard/images', {
            preserveState: true,
            preserveScroll: true,
            onStart: () => {
                statusMessage.value = 'Queueing your image…';
                displayMode.value = 'auto';
                form.clearErrors();
            },
            onSuccess: () => {
                const error = page.props.flash?.error;

                if (error) {
                    statusMessage.value = '';
                    return;
                }

                statusMessage.value = page.props.flash?.status || 'Your image has been queued.';
            },
            onError: (errors) => {
                const message = Object.values(errors)[0] || 'Your settings could not be validated.';
                statusMessage.value = '';
                notify(message, 'error');
            },
        });
}

function buyCredits(amount) {
    router.post('/dashboard/credits', { amount }, {
        preserveScroll: true,
        onSuccess: () => notify(`${amount} credits added to your balance.`),
        onError: () => notify('The purchase could not be completed.', 'error'),
    });
}

function costCaption(generation) {
    if (generation.status === 'failed') {
        return 'No charge';
    }

    if (generation.status === 'succeeded') {
        return generation.user_cost_display ? `Paid ${generation.user_cost_display}` : '';
    }

    return generation.estimated_display ? `Est. ${generation.estimated_display}` : '';
}

function logout() {
    router.post('/logout');
}
</script>

<template>
  <main
    class="studio-page"
    :class="{ 'light-mode': lightMode }"
  >
    <Head title="Image Generator — intellect" />

    <section
      class="dashboard"
      aria-label="Intellect AI image generation dashboard"
    >
      <DashboardSidebar
        :mobile-open="mobileOpen"
        :is-admin="user?.is_admin"
        :balance-display="balanceDisplay"
        @close="mobileOpen = false"
        @unavailable="notify(`${$event} is outside this image-generation release.`)"
        @buy="buyCredits"
        @settings="openSettings"
      />
      <button
        v-if="mobileOpen"
        type="button"
        class="navigation-overlay"
        aria-label="Close navigation"
        @click="mobileOpen = false"
      />

      <header class="topbar">
        <button
          type="button"
          class="round-button mobile-menu"
          aria-label="Open navigation"
          @click="mobileOpen = true"
        >
          <AppIcon
            name="menu"
            :size="22"
          />
        </button>
        <label class="search-box">
          <AppIcon
            name="search"
            :size="25"
          />
          <input
            v-model="search"
            type="search"
            placeholder="Search your creations…"
            aria-label="Search creations"
          >
        </label>
        <div class="topbar-actions">
          <button
            type="button"
            class="round-button"
            aria-label="Notifications"
            @click="notify(hasPendingGenerations ? 'Your images are still being created.' : 'You’re all caught up.')"
          >
            <AppIcon
              name="bell"
              :size="23"
            />
            <span
              v-if="hasPendingGenerations"
              class="notification-dot"
            />
          </button>
          <button
            type="button"
            class="round-button"
            aria-label="Open generation settings"
            @click="openSettings"
          >
            <AppIcon
              name="settings"
              :size="23"
            />
          </button>
          <button
            type="button"
            class="round-button"
            :aria-label="lightMode ? 'Switch to dark ambience' : 'Switch to light ambience'"
            @click="lightMode = !lightMode"
          >
            <AppIcon
              :name="lightMode ? 'sun' : 'moon'"
              :size="23"
            />
          </button>
          <div class="profile-menu">
            <button
              type="button"
              class="avatar"
              :aria-expanded="profileOpen"
              aria-label="Open account menu"
              @click="profileOpen = !profileOpen"
            >
              {{ userInitial }}
            </button>
            <div
              v-if="profileOpen"
              class="profile-dropdown"
            >
              <div class="profile-details">
                <strong>{{ user?.name }}</strong>
                <span>{{ user?.email }}</span>
              </div>
              <button
                type="button"
                @click="logout"
              >
                <AppIcon
                  name="logout"
                  :size="18"
                />
                Log out
              </button>
            </div>
          </div>
        </div>
      </header>

      <div
        class="workspace-body"
        :class="{ 'settings-open': settingsOpen }"
      >
        <section
          class="generator-main"
          aria-labelledby="generator-title"
        >
          <div class="generator-heading">
            <div class="heading-row">
              <h1 id="generator-title">
                Generate Images
              </h1>
              <button
                type="button"
                class="more-button"
                aria-label="Reset generator settings"
                title="Reset generator settings"
                :disabled="isGenerating"
                @click="resetSettings"
              >
                <AppIcon
                  name="dots"
                  :size="23"
                />
              </button>
            </div>
            <div class="generation-tags">
              <span>Text-to-Image</span>
              <button
                type="button"
                title="Change image size"
                @click="cycleSize"
              >{{ size.replace('x', ' × ') }}</button>
              <button
                type="button"
                title="Change quality"
                @click="cycleQuality"
              >{{ quality.charAt(0).toUpperCase() + quality.slice(1) }} Quality</button>
              <span>{{ referenceImages.length }} {{ referenceImages.length === 1 ? 'Reference' : 'References' }}</span>
            </div>
            <div class="prompt-summary">
              <button
                type="button"
                aria-label="Copy current prompt"
                title="Copy current prompt"
                @click="copyPrompt"
              >
                <AppIcon
                  name="copy"
                  :size="22"
                />
              </button>
              <p>{{ prompt }}</p>
            </div>
          </div>

          <div
            class="result-gallery"
            :class="{ 'is-empty': filteredGenerations.length === 0 }"
            aria-live="polite"
            :aria-busy="isGenerating || hasPendingGenerations"
          >
            <p
              v-if="filteredGenerations.length === 0"
              class="empty-results"
            >
              No creations match “{{ search }}”.
            </p>
            <article
              v-for="(generation, index) in filteredGenerations"
              :key="generation.id"
              class="result-card"
              :class="[`tilt-${index % 3}`, { failed: generation.status === 'failed' }]"
              :title="generation.prompt"
            >
              <a
                v-if="generation.status === 'succeeded' && generation.image_url"
                :href="generation.image_url"
                target="_blank"
                rel="noopener"
                class="image-link"
                :aria-label="`Open generated image: ${generation.prompt}`"
              >
                <img
                  :src="generation.image_url"
                  :alt="generation.prompt"
                  loading="lazy"
                >
                <span class="open-overlay">
                  <AppIcon
                    name="download"
                    :size="22"
                  />
                  Open image
                </span>
              </a>
              <div
                v-else-if="generation.status === 'failed'"
                class="generation-placeholder failed-placeholder"
              >
                <AppIcon
                  name="alert"
                  :size="28"
                />
                <span>Generation failed</span>
                <small>{{ generation.error?.message || 'Please try another prompt.' }}</small>
              </div>
              <div
                v-else
                class="generation-placeholder loading-placeholder"
              >
                <span class="placeholder-orb" />
                <span>{{ generation.status === 'processing' ? 'Creating your image' : 'Waiting in queue' }}</span>
                <small>PixelForge is at work</small>
              </div>
              <p
                v-if="costCaption(generation)"
                class="cost-caption"
                data-testid="cost-caption"
              >
                {{ costCaption(generation) }}
              </p>
            </article>
          </div>

          <div class="generator-actions">
            <button
              type="button"
              class="generate-more"
              :disabled="isGenerating || !prompt.trim()"
              @click="generateImages"
            >
              {{ isGenerating ? 'Queueing…' : 'Generate More' }}
              <AppIcon
                :name="isGenerating ? 'refresh' : 'plus'"
                :size="20"
                :class="{ rotating: isGenerating }"
              />
            </button>
            <div
              class="creation-tools"
              aria-label="Creation tools"
            >
              <button
                type="button"
                class="active"
                aria-label="Edit prompt"
                title="Edit prompt"
                @click="focusPrompt"
              >
                <AppIcon
                  name="type"
                  :size="22"
                />
              </button>
              <button
                type="button"
                aria-label="Clear prompt"
                title="Clear prompt"
                :disabled="isGenerating"
                @click="prompt = ''; focusPrompt()"
              >
                <AppIcon
                  name="eraser"
                  :size="22"
                />
              </button>
              <button
                type="button"
                aria-label="Enhance prompt"
                title="Enhance prompt"
                @click="enhancePrompt"
              >
                <AppIcon
                  name="wand"
                  :size="22"
                />
              </button>
              <button
                type="button"
                aria-label="Change canvas size"
                title="Change canvas size"
                @click="cycleSize"
              >
                <AppIcon
                  name="crop"
                  :size="22"
                />
              </button>
              <button
                type="button"
                aria-label="Change image quality"
                title="Change image quality"
                @click="cycleQuality"
              >
                <AppIcon
                  name="detail"
                  :size="22"
                />
              </button>
              <button
                type="button"
                aria-label="Restore showcase"
                title="Restore showcase"
                @click="displayMode = 'showcase'"
              >
                <AppIcon
                  name="image"
                  :size="22"
                />
              </button>
            </div>
          </div>
        </section>

        <aside
          id="generation-settings"
          class="settings-wrap"
          aria-label="Generation controls"
        >
          <GeneratorSettings
            v-model:prompt="prompt"
            v-model:quality="quality"
            v-model:size="size"
            v-model:reference-images="referenceImages"
            :is-generating="isGenerating"
            :status-message="statusMessage"
            :estimate-label="estimateLabel"
            @generate="generateImages"
            @enhance="enhancePrompt"
          />
        </aside>
      </div>
    </section>

    <AppToasts />
  </main>
</template>

<style scoped>
.studio-page {
  --page-gutter: 0px;
  --accent: #ffd400;
  --accent-hover: #ffdf32;
  --text: #f3f4e9;
  --muted: #c0c3b5;
  --border: rgb(255 255 255 / 11%);
  --subtle-border: rgb(255 255 255 / 3%);
  --control: rgb(255 255 255 / 7%);
  --sidebar: rgb(135 144 109 / 58%);
  --topbar: rgb(98 108 85 / 46%);
  --workspace: rgb(13 27 21 / 68%);
  --settings-surface: rgb(80 83 61 / 55%);
  --slider-track: rgb(255 255 255 / 12%);
  position: relative;
  display: flex;
  min-height: 100svh;
  padding: 0;
  overflow: hidden;
  color: var(--text);
  background:
    radial-gradient(circle at 13% 25%, rgb(146 154 72 / 40%), transparent 29%),
    radial-gradient(circle at 74% 42%, rgb(12 72 58 / 43%), transparent 36%),
    radial-gradient(circle at 67% 95%, rgb(108 94 14 / 32%), transparent 35%),
    #3d390c;
  isolation: isolate;
}

.studio-page::before {
  position: fixed;
  z-index: -1;
  inset: -80px;
  pointer-events: none;
  content: '';
  background:
    radial-gradient(ellipse at 23% 35%, rgb(202 183 66 / 16%), transparent 27%),
    radial-gradient(ellipse at 70% 20%, rgb(0 79 62 / 22%), transparent 35%);
  filter: blur(55px);
}

.studio-page.light-mode {
  --text: #282c20;
  --muted: #5e6454;
  --border: rgb(43 53 37 / 12%);
  --subtle-border: rgb(43 53 37 / 5%);
  --control: rgb(255 255 255 / 31%);
  --sidebar: rgb(213 216 183 / 75%);
  --topbar: rgb(221 222 198 / 66%);
  --workspace: rgb(224 229 205 / 78%);
  --settings-surface: rgb(206 207 176 / 74%);
  --slider-track: rgb(42 48 32 / 16%);
  background:
    radial-gradient(circle at 13% 25%, rgb(232 220 130 / 65%), transparent 31%),
    radial-gradient(circle at 74% 42%, rgb(148 190 161 / 58%), transparent 38%),
    #c5c18a;
}

.dashboard {
  display: grid;
  width: 100%;
  min-height: 100svh;
  grid-template-columns: clamp(230px, 18vw, 325px) minmax(0, 1fr);
  grid-template-rows: clamp(76px, 5.7vw, 115px) minmax(0, 1fr);
  margin: auto;
  overflow: hidden;
  background: var(--workspace);
  border: 0;
  border-radius: 0;
}

.topbar {
  position: relative;
  z-index: 15;
  display: flex;
  min-width: 0;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 0 clamp(20px, 1.55vw, 32px);
  background: var(--topbar);
  border-bottom: 1px solid var(--border);
  backdrop-filter: blur(28px);
}

.search-box {
  display: flex;
  width: min(50%, 560px);
  min-height: clamp(48px, 3.6vw, 72px);
  align-items: center;
  gap: 15px;
  padding: 0 clamp(17px, 1.25vw, 25px);
  color: var(--text);
  background: rgb(255 255 255 / 8%);
  border: 1px solid var(--border);
  border-radius: 999px;
  box-shadow: inset 0 1px 0 rgb(255 255 255 / 5%);
}

.search-box input {
  width: 100%;
  min-width: 0;
  color: var(--text);
  font: inherit;
  font-size: clamp(15px, 0.95vw, 19px);
  background: transparent;
  border: 0;
  outline: 0;
}

.search-box input::placeholder {
  color: var(--text);
  opacity: 0.8;
}

.search-box input::-webkit-search-cancel-button {
  filter: grayscale(1);
}

.search-box:focus-within {
  border-color: var(--accent);
  box-shadow: 0 0 0 2px rgb(255 212 0 / 12%);
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: clamp(7px, 0.55vw, 11px);
}

.round-button {
  position: relative;
  display: inline-grid;
  width: clamp(42px, 2.75vw, 56px);
  height: clamp(42px, 2.75vw, 56px);
  flex: 0 0 auto;
  place-items: center;
  color: var(--text);
  cursor: pointer;
  background: rgb(255 255 255 / 8%);
  border: 1px solid var(--border);
  border-radius: 50%;
  transition: background 160ms ease, transform 160ms ease;
}

.round-button:hover {
  background: rgb(255 255 255 / 15%);
  transform: translateY(-1px);
}

.notification-dot {
  position: absolute;
  top: 9px;
  right: 9px;
  width: 7px;
  height: 7px;
  background: var(--accent);
  border: 2px solid var(--topbar);
  border-radius: 50%;
}

.mobile-menu {
  display: none;
}

.profile-menu {
  position: relative;
  margin-left: 3px;
}

.avatar {
  display: inline-grid;
  width: clamp(44px, 2.85vw, 58px);
  height: clamp(44px, 2.85vw, 58px);
  place-items: center;
  color: white;
  font: inherit;
  font-size: 20px;
  font-weight: 600;
  cursor: pointer;
  background:
    radial-gradient(circle at 65% 25%, #8affdb, transparent 35%),
    linear-gradient(140deg, #e35fba, #6356d9 48%, #00b5ba);
  border: 2px solid rgb(255 255 255 / 20%);
  border-radius: 50%;
  box-shadow: 0 5px 20px rgb(31 20 70 / 20%);
}

.profile-dropdown {
  position: absolute;
  top: calc(100% + 13px);
  right: 0;
  width: 230px;
  padding: 9px;
  color: #f3f4e9;
  background: rgb(43 50 39 / 96%);
  border: 1px solid rgb(255 255 255 / 12%);
  border-radius: 16px;
  box-shadow: 0 20px 55px rgb(0 0 0 / 30%);
  backdrop-filter: blur(24px);
}

.profile-details {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 3px;
  padding: 10px 11px 13px;
  border-bottom: 1px solid rgb(255 255 255 / 9%);
}

.profile-details strong,
.profile-details span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-details strong {
  font-size: 14px;
  font-weight: 500;
}

.profile-details span {
  color: #bfc4b6;
  font-size: 12px;
}

.profile-dropdown button {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 9px;
  padding: 11px;
  margin-top: 5px;
  color: #f3f4e9;
  font: inherit;
  font-size: 14px;
  cursor: pointer;
  background: transparent;
  border: 0;
  border-radius: 10px;
}

.profile-dropdown button:hover {
  background: rgb(255 255 255 / 8%);
}

.workspace-body {
  display: grid;
  min-width: 0;
  min-height: 0;
  grid-template-columns: minmax(0, 1fr) clamp(315px, 22.5vw, 440px);
}

.generator-main {
  display: flex;
  min-width: 0;
  min-height: 0;
  flex-direction: column;
  padding: clamp(24px, 2vw, 40px) clamp(20px, 1.55vw, 32px) clamp(22px, 1.45vw, 30px);
  overflow: hidden;
  background:
    radial-gradient(circle at 0% 0%, rgb(32 90 65 / 22%), transparent 40%),
    radial-gradient(circle at 75% 65%, rgb(90 79 12 / 12%), transparent 50%);
}

.generator-heading {
  flex: 0 0 auto;
}

.heading-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.heading-row h1 {
  margin: 0;
  font-size: clamp(27px, 1.65vw, 34px);
  font-weight: 500;
  letter-spacing: -0.04em;
}

.more-button {
  display: inline-grid;
  width: 44px;
  height: 44px;
  place-items: center;
  color: var(--text);
  cursor: pointer;
  background: var(--control);
  border: 1px solid var(--subtle-border);
  border-radius: 12px;
}

.generation-tags {
  display: flex;
  flex-wrap: wrap;
  gap: clamp(7px, 0.55vw, 11px);
  margin-top: clamp(22px, 1.55vw, 31px);
}

.generation-tags span,
.generation-tags button {
  display: inline-flex;
  min-height: clamp(37px, 2.25vw, 46px);
  align-items: center;
  padding: 8px clamp(14px, 1.15vw, 23px);
  color: var(--text);
  font: inherit;
  font-size: clamp(12px, 0.79vw, 16px);
  white-space: nowrap;
  background: var(--control);
  border: 1px solid var(--subtle-border);
  border-radius: 999px;
}

.generation-tags button {
  cursor: pointer;
  transition: background 160ms ease;
}

.generation-tags button:hover {
  background: rgb(255 255 255 / 13%);
}

.prompt-summary {
  display: flex;
  align-items: flex-start;
  gap: 13px;
  margin-top: clamp(18px, 1.2vw, 25px);
}

.prompt-summary button {
  display: inline-grid;
  width: 42px;
  height: 42px;
  flex: 0 0 auto;
  place-items: center;
  color: var(--text);
  cursor: pointer;
  background: var(--control);
  border: 0;
  border-radius: 12px;
}

.prompt-summary p {
  display: -webkit-box;
  max-width: 850px;
  margin: 0;
  overflow: hidden;
  color: var(--muted);
  font-size: clamp(13px, 0.81vw, 17px);
  line-height: 1.65;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}

.result-gallery {
  display: grid;
  min-height: 0;
  flex: 1;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(15px, 1.5vw, 30px);
  align-content: center;
  align-items: center;
  padding: clamp(28px, 2.5vw, 50px) clamp(3px, 0.4vw, 8px);
  overflow-x: hidden;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: var(--control) transparent;
}

.result-gallery.is-empty {
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-results {
  color: var(--muted);
  text-align: center;
}

.result-card {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  overflow: hidden;
  background: rgb(255 255 255 / 7%);
  border: 2px solid rgb(228 234 196 / 29%);
  border-radius: 23%;
  box-shadow: 0 20px 35px rgb(0 0 0 / 22%);
  transition: transform 300ms cubic-bezier(.2,.8,.2,1), border-color 300ms ease;
}

.result-card.tilt-0 {
  transform: rotate(-5.5deg);
}

.result-card.tilt-1 {
  transform: rotate(0.5deg);
}

.result-card.tilt-2 {
  transform: rotate(5.5deg);
}

.result-card:hover {
  z-index: 2;
  border-color: rgb(255 218 42 / 65%);
  transform: translateY(-7px) rotate(0);
}

.image-link {
  position: absolute;
  inset: 0;
  display: block;
  color: white;
  text-decoration: none;
}

.image-link img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 500ms cubic-bezier(.2,.8,.2,1);
}

.result-card:hover .image-link img {
  transform: scale(1.045);
}

.open-overlay {
  position: absolute;
  inset: auto 0 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 28px 10px 14px;
  font-size: 12px;
  font-weight: 500;
  background: linear-gradient(transparent, rgb(0 0 0 / 70%));
  opacity: 0;
  transform: translateY(8px);
  transition: opacity 180ms ease, transform 180ms ease;
}

.result-card:hover .open-overlay,
.image-link:focus-visible .open-overlay {
  opacity: 1;
  transform: translateY(0);
}

.generation-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 8px;
  padding: 18%;
  color: var(--text);
  font-size: clamp(12px, 0.75vw, 15px);
  text-align: center;
  background:
    radial-gradient(circle at 40% 35%, rgb(48 151 127 / 20%), transparent 45%),
    linear-gradient(135deg, rgb(255 255 255 / 8%), rgb(255 255 255 / 2%));
}

.generation-placeholder small {
  color: var(--muted);
  font-size: 10px;
  line-height: 1.4;
}

.cost-caption {
  position: absolute;
  inset: auto 8px 8px;
  z-index: 3;
  display: inline-flex;
  width: fit-content;
  max-width: calc(100% - 16px);
  align-items: center;
  padding: 4px 10px;
  margin: 0;
  overflow: hidden;
  color: var(--text);
  font-size: 11px;
  font-weight: 500;
  white-space: nowrap;
  text-overflow: ellipsis;
  background: rgb(24 30 21 / 72%);
  border: 1px solid rgb(255 255 255 / 10%);
  border-radius: 999px;
  backdrop-filter: blur(10px);
}

.placeholder-orb {
  width: 34px;
  height: 34px;
  margin-bottom: 6px;
  background: conic-gradient(from 30deg, #28dbc1, #f8dd51, #b26cff, #28dbc1);
  border-radius: 42% 58% 61% 39%;
  filter: blur(1px);
  animation: morph 2.4s ease-in-out infinite, spin 4s linear infinite;
}

.failed-placeholder {
  color: #ffd6c9;
  background: rgb(116 46 33 / 30%);
}

.generator-actions {
  display: flex;
  flex: 0 0 auto;
  flex-direction: column;
  align-items: center;
  gap: clamp(18px, 1.45vw, 29px);
}

.generate-more {
  display: inline-flex;
  min-height: clamp(46px, 3.2vw, 64px);
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 10px clamp(22px, 1.55vw, 31px);
  color: var(--text);
  font: inherit;
  font-size: clamp(14px, 0.83vw, 17px);
  cursor: pointer;
  background: var(--control);
  border: 1px solid var(--subtle-border);
  border-radius: 999px;
  transition: background 180ms ease, transform 180ms ease;
}

.generate-more:hover:not(:disabled) {
  background: rgb(255 255 255 / 13%);
  transform: translateY(-2px);
}

.generate-more:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.rotating {
  animation: spin 1.4s linear infinite;
}

.creation-tools {
  display: flex;
  gap: clamp(8px, 0.6vw, 12px);
}

.creation-tools button {
  display: inline-grid;
  width: clamp(42px, 2.9vw, 58px);
  height: clamp(42px, 2.9vw, 58px);
  place-items: center;
  color: var(--text);
  cursor: pointer;
  background: var(--control);
  border: 1px solid var(--subtle-border);
  border-radius: 14px;
  transition: color 180ms ease, background 180ms ease, transform 180ms ease;
}

.creation-tools button:hover {
  color: var(--accent);
  background: rgb(255 255 255 / 13%);
  transform: translateY(-2px);
}

.creation-tools button.active {
  color: var(--text);
  background: linear-gradient(180deg, rgb(255 212 0 / 20%), rgb(255 255 255 / 5%));
  box-shadow: inset 0 -10px 18px rgb(255 205 0 / 8%);
}

.settings-wrap {
  display: flex;
  min-width: 0;
  min-height: 0;
  padding: clamp(18px, 1.55vw, 31px);
  background: var(--settings-surface);
  scroll-margin-top: 15px;
}

.settings-wrap > * {
  width: 100%;
  padding: clamp(15px, 1.15vw, 23px);
  background: rgb(28 33 23 / 12%);
  border: 1px solid var(--subtle-border);
  border-radius: clamp(24px, 1.75vw, 35px);
}

.light-mode .settings-wrap > * {
  background: rgb(255 255 255 / 12%);
}

.light-mode .generation-placeholder small,
.light-mode .failed-placeholder {
  color: var(--muted);
}

.light-mode .generation-tags button:hover,
.light-mode .creation-tools button:hover,
.light-mode .generate-more:hover:not(:disabled),
.light-mode .round-button:hover {
  background: rgb(255 255 255 / 38%);
}

.navigation-overlay {
  display: none;
}

button:focus-visible,
a:focus-visible,
input:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 3px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes morph {
  0%, 100% { border-radius: 42% 58% 61% 39%; transform: scale(0.9); }
  50% { border-radius: 61% 39% 42% 58%; transform: scale(1.1); }
}

@media (min-width: 1700px) {
  .generator-main {
    padding-right: 30px;
    padding-left: 30px;
  }
}

@media (max-width: 1300px) {
  .studio-page {
    --page-gutter: 20px;
  }

  .dashboard {
    grid-template-columns: 230px minmax(0, 1fr);
  }

  .workspace-body {
    grid-template-columns: minmax(0, 1fr) 315px;
  }

  .generator-main {
    padding: 24px 20px 20px;
  }

  .generation-tags {
    gap: 6px;
    margin-top: 20px;
  }

  .generation-tags span,
  .generation-tags button {
    padding: 7px 13px;
  }

  .prompt-summary p {
    -webkit-line-clamp: 2;
  }

  .result-gallery {
    gap: 17px;
    padding-top: 25px;
    padding-bottom: 25px;
  }

  .creation-tools {
    gap: 7px;
  }
}

@media (max-width: 1100px) {
  .studio-page {
    --page-gutter: 14px;
    overflow: visible;
  }

  .dashboard {
    grid-template-columns: minmax(0, 1fr);
    min-height: 100svh;
  }

  .mobile-menu {
    display: inline-grid;
  }

  .search-box {
    width: min(60%, 520px);
    margin-right: auto;
  }

  .navigation-overlay {
    position: fixed;
    z-index: 50;
    inset: 0;
    display: block;
    cursor: pointer;
    background: rgb(5 10 7 / 55%);
    border: 0;
    backdrop-filter: blur(5px);
  }

  .generator-main {
    min-height: 680px;
  }
}

@media (max-width: 760px) {
  .studio-page {
    --page-gutter: 0px;
  }

  .dashboard {
    min-height: 100svh;
    border: 0;
  }

  .topbar {
    position: sticky;
    top: 0;
    gap: 10px;
    min-height: 74px;
    padding: 10px 14px;
  }

  .topbar-actions {
    gap: 7px;
  }

  .topbar-actions .round-button:first-child,
  .topbar-actions .round-button:nth-child(3) {
    display: none;
  }

  .round-button,
  .avatar {
    width: 42px;
    height: 42px;
  }

  .search-box {
    width: auto;
    min-height: 43px;
    flex: 1;
    gap: 9px;
    padding: 0 13px;
  }

  .search-box svg {
    width: 20px;
  }

  .search-box input {
    font-size: 14px;
  }

  .workspace-body {
    display: block;
  }

  .generator-main {
    min-height: 650px;
    padding: 25px 17px 24px;
    overflow: visible;
  }

  .generation-tags {
    flex-wrap: nowrap;
    margin-right: -17px;
    overflow-x: auto;
    scrollbar-width: none;
  }

  .generation-tags::-webkit-scrollbar {
    display: none;
  }

  .prompt-summary p {
    font-size: 13px;
    -webkit-line-clamp: 3;
  }

  .result-gallery {
    min-height: 290px;
    gap: 15px;
    padding: 35px 5px;
    overflow: visible;
  }

  .result-card {
    border-radius: 20%;
  }

  .generation-placeholder {
    padding: 12%;
  }

  .generation-placeholder small,
  .generation-placeholder > span:not(.placeholder-orb) {
    display: none;
  }

  .placeholder-orb {
    width: 27px;
    height: 27px;
  }

  .creation-tools {
    gap: 7px;
  }

  .creation-tools button {
    width: 43px;
    height: 43px;
    border-radius: 12px;
  }

  .settings-wrap {
    display: none;
    padding: 15px;
  }

  .settings-open .settings-wrap {
    display: flex;
  }

  .settings-wrap > * {
    padding: 15px;
    border-radius: 27px;
  }

}

@media (max-width: 390px) {
  .topbar-actions .round-button:nth-child(2) {
    display: none;
  }

  .generator-main {
    min-height: 600px;
  }

  .creation-tools button {
    width: 40px;
    height: 40px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .result-card,
  .image-link img,
  .open-overlay,
  .round-button,
  .generate-more,
  .creation-tools button {
    transition: none;
  }

  .placeholder-orb,
  .rotating {
    animation-duration: 4s;
  }
}
</style>
