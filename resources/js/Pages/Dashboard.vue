<script setup>
import { Head, router, useForm, usePage, usePoll } from '@inertiajs/vue3';
import { Input, InputClear, InputGroup } from '@ui-sdk/vue/input';
import { Menu, MenuTrigger } from '@ui-sdk/vue/menu';
import { computed, nextTick, ref, watch } from 'vue';

import AppIcon from '../components/AppIcon.vue';
import AppToasts from '../components/AppToasts.vue';
import DashboardSidebar from '../components/dashboard/DashboardSidebar.vue';
import GeneratorSettings from '../components/dashboard/GeneratorSettings.vue';
import { Button } from '../components/ui/button';
import { MenuContent, MenuItem, MenuItemIcon } from '../components/ui/menu';
import { notify } from '../lib/toast';
import { computeEstimateNd, formatRangeNd } from '../lib/pricing';
import { useTheme } from '../composables/useTheme';

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
const { dark, toggle: toggleTheme } = useTheme();
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
    class="relative flex min-h-[100svh] isolate overflow-hidden text-foreground bg-ambience bg-[radial-gradient(circle_at_13%_25%,rgb(232_220_130/65%),transparent_31%),radial-gradient(circle_at_74%_42%,rgb(148_190_161/58%),transparent_38%)] dark:bg-[radial-gradient(circle_at_13%_25%,rgb(146_154_72/40%),transparent_29%),radial-gradient(circle_at_74%_42%,rgb(12_72_58/43%),transparent_36%),radial-gradient(circle_at_67%_95%,rgb(108_94_14/32%),transparent_35%)]"
  >
    <div
      class="pointer-events-none fixed -inset-20 -z-10 bg-[radial-gradient(ellipse_at_23%_35%,rgb(202_183_66/16%),transparent_27%),radial-gradient(ellipse_at_70%_20%,rgb(0_79_62/22%),transparent_35%)] blur-[55px]"
      aria-hidden="true"
    />
    <Head title="Image Generator — intellect" />

    <section
      class="grid w-full min-h-[100svh] m-auto grid-cols-[clamp(230px,18vw,325px)_minmax(0,1fr)] grid-rows-[clamp(76px,5.7vw,115px)_minmax(0,1fr)] overflow-hidden bg-card max-[1300px]:grid-cols-[230px_minmax(0,1fr)] max-[1100px]:grid-cols-[minmax(0,1fr)]"
      aria-label="Intellect AI image generation dashboard"
    >
      <DashboardSidebar
        :mobile-open="mobileOpen"
        :is-admin="user?.is_admin"
        :balance-display="balanceDisplay"
        @close="mobileOpen = false"
        @buy="buyCredits"
        @settings="openSettings"
      />
      <Button
        v-if="mobileOpen"
        variant="circle"
        size="none"
        class="hidden max-[1100px]:fixed max-[1100px]:inset-0 max-[1100px]:z-50 max-[1100px]:block max-[1100px]:cursor-pointer bg-[rgb(5_10_7/55%)] border-0 backdrop-blur-[5px] hover:bg-[rgb(5_10_7/55%)] dark:hover:bg-[rgb(5_10_7/55%)]"
        aria-label="Close navigation"
        @click="mobileOpen = false"
      />

      <header class="relative z-15 flex min-w-0 items-center justify-between gap-6 px-[clamp(20px,1.55vw,32px)] bg-topbar border-b border-border backdrop-blur-[28px] max-[760px]:sticky max-[760px]:top-0 max-[760px]:gap-2.5 max-[760px]:min-h-[74px] max-[760px]:py-2.5 max-[760px]:px-3.5">
        <Button
          variant="circle"
          size="none"
          class="relative hidden max-[1100px]:inline-grid max-[760px]:size-[42px]"
          aria-label="Open navigation"
          @click="mobileOpen = true"
        >
          <AppIcon
            name="menu"
            :size="22"
          />
        </Button>
        <InputGroup
          class="flex w-[min(50%,560px)] min-h-[clamp(48px,3.6vw,72px)] items-center gap-[15px] px-[clamp(17px,1.25vw,25px)] text-foreground bg-secondary border border-border rounded-full shadow-[inset_0_1px_0_rgb(255_255_255/5%)] focus-within:border-ring focus-within:shadow-[0_0_0_2px_rgb(255_212_0/12%)] max-[1100px]:w-[min(60%,520px)] max-[1100px]:mr-auto max-[760px]:w-auto max-[760px]:flex-1 max-[760px]:min-h-[43px] max-[760px]:gap-[9px] max-[760px]:px-[13px]"
        >
          <AppIcon
            name="search"
            :size="25"
            class="shrink-0 max-[760px]:size-5"
          />
          <Input
            v-model="search"
            type="search"
            placeholder="Search your creations…"
            aria-label="Search creations"
            class="w-full min-w-0 bg-transparent border-0 outline-none text-[clamp(15px,0.95vw,19px)] placeholder:text-foreground/80 [&::-webkit-search-cancel-button]:hidden max-[760px]:text-sm"
          />
          <InputClear
            class="mr-1 inline-grid size-6 shrink-0 cursor-pointer place-items-center rounded-md text-muted-foreground hover:text-foreground"
            aria-label="Clear search"
          >
            <AppIcon
              name="close"
              :size="16"
            />
          </InputClear>
        </InputGroup>
        <div class="flex items-center gap-[clamp(7px,0.55vw,11px)] max-[760px]:gap-[7px]">
          <Button
            variant="circle"
            size="none"
            class="relative max-[760px]:hidden max-[760px]:size-[42px]"
            aria-label="Notifications"
            @click="notify(hasPendingGenerations ? 'Your images are still being created.' : 'You’re all caught up.')"
          >
            <AppIcon
              name="bell"
              :size="23"
            />
            <span
              v-if="hasPendingGenerations"
              class="absolute top-[9px] right-[9px] size-[7px] bg-primary border-2 border-topbar rounded-full"
            />
          </Button>
          <Button
            variant="circle"
            size="none"
            class="max-[760px]:size-[42px] max-[390px]:hidden"
            aria-label="Open generation settings"
            @click="openSettings"
          >
            <AppIcon
              name="settings"
              :size="23"
            />
          </Button>
          <Button
            variant="circle"
            size="none"
            class="max-[760px]:hidden max-[760px]:size-[42px]"
            :aria-label="dark ? 'Switch to light ambience' : 'Switch to dark ambience'"
            @click="toggleTheme"
          >
            <AppIcon
              :name="dark ? 'sun' : 'moon'"
              :size="23"
            />
          </Button>
          <div class="relative ml-[3px]">
            <Menu>
              <MenuTrigger
                class="inline-grid size-[clamp(44px,2.85vw,58px)] place-items-center text-white text-xl font-semibold border-2 border-white/20 rounded-full bg-[radial-gradient(circle_at_65%_25%,#8affdb,transparent_35%),linear-gradient(140deg,#e35fba,#6356d9_48%,#00b5ba)] shadow-[0_5px_20px_rgb(31_20_70/20%)] max-[760px]:size-[42px]"
                aria-label="Open account menu"
              >
                {{ userInitial }}
              </MenuTrigger>
              <MenuContent
                align="end"
                :side-offset="13"
              >
                <div class="flex min-w-0 flex-col gap-[3px] pt-2.5 px-[11px] pb-[13px] border-b border-border">
                  <strong class="truncate text-sm font-medium">{{ user?.name }}</strong>
                  <span class="truncate text-xs text-muted-foreground">{{ user?.email }}</span>
                </div>
                <MenuItem
                  value="logout"
                  class="mt-[5px]"
                  @select="logout"
                >
                  <MenuItemIcon>
                    <AppIcon
                      name="logout"
                      :size="18"
                    />
                  </MenuItemIcon>
                  Log out
                </MenuItem>
              </MenuContent>
            </Menu>
          </div>
        </div>
      </header>

      <div class="grid min-w-0 min-h-0 grid-cols-[minmax(0,1fr)_clamp(315px,22.5vw,440px)] max-[1300px]:grid-cols-[minmax(0,1fr)_315px] max-[760px]:block">
        <section
          class="flex min-w-0 min-h-0 flex-col pt-[clamp(24px,2vw,40px)] px-[clamp(20px,1.55vw,32px)] pb-[clamp(22px,1.45vw,30px)] overflow-hidden bg-[radial-gradient(circle_at_0%_0%,rgb(32_90_65/22%),transparent_40%),radial-gradient(circle_at_75%_65%,rgb(90_79_12/12%),transparent_50%)] min-[1700px]:px-[30px] max-[1300px]:pt-6 max-[1300px]:px-5 max-[1300px]:pb-5 max-[1100px]:min-h-[680px] max-[760px]:min-h-[650px] max-[760px]:pt-[25px] max-[760px]:px-[17px] max-[760px]:pb-6 max-[760px]:overflow-visible"
          aria-labelledby="generator-title"
        >
          <div class="shrink-0">
            <div class="flex items-center justify-between gap-5">
              <h1 id="generator-title" class="m-0 text-[clamp(27px,1.65vw,34px)] font-medium tracking-[-0.04em]">
                Generate Images
              </h1>
              <Button
                variant="panel"
                size="icon"
                aria-label="Reset generator settings"
                title="Reset generator settings"
                :disabled="isGenerating"
                @click="resetSettings"
              >
                <AppIcon
                  name="dots"
                  :size="23"
                />
              </Button>
            </div>
            <div class="flex flex-wrap gap-[clamp(7px,0.55vw,11px)] mt-[clamp(22px,1.55vw,31px)] max-[1300px]:gap-1.5 max-[1300px]:mt-5 max-[760px]:flex-nowrap max-[760px]:-mr-[17px] max-[760px]:overflow-x-auto max-[760px]:[scrollbar-width:none] max-[760px]:[&::-webkit-scrollbar]:hidden">
              <span class="inline-flex min-h-[clamp(37px,2.25vw,46px)] items-center py-2 px-[clamp(14px,1.15vw,23px)] text-foreground text-[clamp(12px,0.79vw,16px)] whitespace-nowrap bg-secondary border border-border-subtle rounded-full max-[1300px]:py-[7px] max-[1300px]:px-[13px]">Text-to-Image</span>
              <Button
                variant="chip"
                size="none"
                title="Change image size"
                @click="cycleSize"
              >{{ size.replace('x', ' × ') }}</Button>
              <Button
                variant="chip"
                size="none"
                title="Change quality"
                @click="cycleQuality"
              >{{ quality.charAt(0).toUpperCase() + quality.slice(1) }} Quality</Button>
              <span class="inline-flex min-h-[clamp(37px,2.25vw,46px)] items-center py-2 px-[clamp(14px,1.15vw,23px)] text-foreground text-[clamp(12px,0.79vw,16px)] whitespace-nowrap bg-secondary border border-border-subtle rounded-full max-[1300px]:py-[7px] max-[1300px]:px-[13px]">{{ referenceImages.length }} {{ referenceImages.length === 1 ? 'Reference' : 'References' }}</span>
            </div>
            <div class="flex items-start gap-[13px] mt-[clamp(18px,1.2vw,25px)]">
              <Button
                variant="panel"
                size="none"
                class="size-[42px] border-0"
                aria-label="Copy current prompt"
                title="Copy current prompt"
                @click="copyPrompt"
              >
                <AppIcon
                  name="copy"
                  :size="22"
                />
              </Button>
              <p class="m-0 max-w-[850px] overflow-hidden text-muted-foreground text-[clamp(13px,0.81vw,17px)] leading-[1.65] line-clamp-3 max-[1300px]:line-clamp-2 max-[760px]:text-[13px] max-[760px]:line-clamp-3">
                {{ prompt }}
              </p>
            </div>
          </div>

          <div
            class="grid min-h-0 flex-1 content-center items-center grid-cols-[repeat(3,minmax(0,1fr))] gap-[clamp(15px,1.5vw,30px)] py-[clamp(28px,2.5vw,50px)] px-[clamp(3px,0.4vw,8px)] overflow-x-hidden overflow-y-auto [scrollbar-width:thin] [scrollbar-color:var(--secondary)_transparent] max-[1300px]:gap-[17px] max-[1300px]:py-[25px] max-[760px]:min-h-[290px] max-[760px]:gap-[15px] max-[760px]:py-[35px] max-[760px]:px-[5px] max-[760px]:overflow-visible"
            :class="{ 'flex items-center justify-center': filteredGenerations.length === 0 }"
            aria-live="polite"
            :aria-busy="isGenerating || hasPendingGenerations"
          >
            <p
              v-if="filteredGenerations.length === 0"
              class="text-muted-foreground text-center"
            >
              No creations match “{{ search }}”.
            </p>
            <article
              v-for="(generation, index) in filteredGenerations"
              :key="generation.id"
              class="group relative w-full aspect-square overflow-hidden bg-secondary border-2 border-[rgb(228_234_196/29%)] rounded-[23%] shadow-[0_20px_35px_rgb(0_0_0/22%)] [transition:transform_300ms_cubic-bezier(0.2,0.8,0.2,1),border-color_300ms_ease] motion-reduce:transition-none hover:z-[2] hover:[transform:translateY(-7px)_rotate(0deg)] hover:border-[rgb(255_218_42/65%)] max-[760px]:rounded-[20%]"
              :class="['[transform:rotate(-5.5deg)]', '[transform:rotate(0.5deg)]', '[transform:rotate(5.5deg)]'][index % 3]"
              :title="generation.prompt"
            >
              <a
                v-if="generation.status === 'succeeded' && generation.image_url"
                :href="generation.image_url"
                target="_blank"
                rel="noopener"
                class="absolute inset-0 block text-white no-underline"
                :aria-label="`Open generated image: ${generation.prompt}`"
              >
                <img
                  :src="generation.image_url"
                  :alt="generation.prompt"
                  loading="lazy"
                  class="w-full h-full object-cover transition-transform duration-500 [transition-timing-function:cubic-bezier(0.2,0.8,0.2,1)] group-hover:scale-[1.045] motion-reduce:transition-none"
                >
                <span class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 pt-[28px] px-[10px] pb-[14px] text-xs font-medium text-white bg-[linear-gradient(transparent,rgb(0_0_0/70%))] opacity-0 translate-y-2 transition-[opacity,transform] duration-[180ms] ease-[ease] group-hover:opacity-100 group-hover:translate-y-0 group-focus-within:opacity-100 group-focus-within:translate-y-0 motion-reduce:transition-none">
                  <AppIcon
                    name="download"
                    :size="22"
                  />
                  Open image
                </span>
              </a>
              <div
                v-else-if="generation.status === 'failed'"
                class="absolute inset-0 flex items-center justify-center flex-col gap-2 p-[18%] text-center text-muted-foreground dark:text-[#ffd6c9] text-[clamp(12px,0.75vw,15px)] bg-[rgb(116_46_33/30%)] max-[760px]:p-[12%]"
              >
                <AppIcon
                  name="alert"
                  :size="28"
                />
                <span>Generation failed</span>
                <small class="text-[10px] leading-[1.4] max-[760px]:hidden">{{ generation.error?.message || 'Please try another prompt.' }}</small>
              </div>
              <div
                v-else
                class="absolute inset-0 flex items-center justify-center flex-col gap-2 p-[18%] text-foreground text-center text-[clamp(12px,0.75vw,15px)] bg-[radial-gradient(circle_at_40%_35%,rgb(48_151_127/20%),transparent_45%),linear-gradient(135deg,rgb(255_255_255/8%),rgb(255_255_255/2%))] max-[760px]:p-[12%]"
              >
                <span class="size-[34px] mb-1.5 bg-[conic-gradient(from_30deg,#28dbc1,#f8dd51,#b26cff,#28dbc1)] rounded-[42%_58%_61%_39%] blur-[1px] [animation:morph_2.4s_ease-in-out_infinite,spin_4s_linear_infinite] motion-reduce:[animation-duration:4s] max-[760px]:size-[27px]" />
                <span class="max-[760px]:hidden">{{ generation.status === 'processing' ? 'Creating your image' : 'Waiting in queue' }}</span>
                <small class="text-muted-foreground text-[10px] leading-[1.4] max-[760px]:hidden">PixelForge is at work</small>
              </div>
              <p
                v-if="costCaption(generation)"
                data-testid="cost-caption"
                class="absolute inset-x-2 bottom-2 z-[3] inline-flex w-fit max-w-[calc(100%-16px)] items-center py-1 px-2.5 m-0 overflow-hidden text-foreground text-[11px] font-medium whitespace-nowrap bg-[rgb(24_30_21/72%)] border border-white/10 rounded-full backdrop-blur-[10px]"
              >
                {{ costCaption(generation) }}
              </p>
            </article>
          </div>

          <div class="flex shrink-0 flex-col items-center gap-[clamp(18px,1.45vw,29px)]">
            <Button
              variant="chip"
              size="none"
              class="min-h-[clamp(46px,3.2vw,64px)] px-[clamp(22px,1.55vw,31px)] text-[clamp(14px,0.83vw,17px)] hover:-translate-y-0.5"
              :disabled="isGenerating || !prompt.trim()"
              focusable-when-disabled
              @click="generateImages"
            >
              {{ isGenerating ? 'Queueing…' : 'Generate More' }}
              <AppIcon
                :name="isGenerating ? 'refresh' : 'plus'"
                :size="20"
                :class="isGenerating ? 'animate-[spin_1.4s_linear_infinite]' : ''"
              />
            </Button>
            <div
              class="flex gap-[clamp(8px,0.6vw,12px)] max-[1300px]:gap-[7px] max-[760px]:gap-[7px]"
              aria-label="Creation tools"
            >
              <Button
                variant="tool"
                size="none"
                class="bg-[linear-gradient(180deg,rgb(255_212_0/20%),rgb(255_255_255/5%))] shadow-[inset_0_-10px_18px_rgb(255_205_0/8%)] hover:text-foreground"
                aria-label="Edit prompt"
                title="Edit prompt"
                @click="focusPrompt"
              >
                <AppIcon
                  name="type"
                  :size="22"
                />
              </Button>
              <Button
                variant="tool"
                size="none"
                aria-label="Clear prompt"
                title="Clear prompt"
                :disabled="isGenerating"
                @click="prompt = ''; focusPrompt()"
              >
                <AppIcon
                  name="eraser"
                  :size="22"
                />
              </Button>
              <Button
                variant="tool"
                size="none"
                aria-label="Enhance prompt"
                title="Enhance prompt"
                @click="enhancePrompt"
              >
                <AppIcon
                  name="wand"
                  :size="22"
                />
              </Button>
              <Button
                variant="tool"
                size="none"
                aria-label="Change canvas size"
                title="Change canvas size"
                @click="cycleSize"
              >
                <AppIcon
                  name="crop"
                  :size="22"
                />
              </Button>
              <Button
                variant="tool"
                size="none"
                aria-label="Change image quality"
                title="Change image quality"
                @click="cycleQuality"
              >
                <AppIcon
                  name="detail"
                  :size="22"
                />
              </Button>
              <Button
                variant="tool"
                size="none"
                aria-label="Restore showcase"
                title="Restore showcase"
                @click="displayMode = 'showcase'"
              >
                <AppIcon
                  name="image"
                  :size="22"
                />
              </Button>
            </div>
          </div>
        </section>

        <aside
          id="generation-settings"
          class="flex min-w-0 min-h-0 p-[clamp(18px,1.55vw,31px)] bg-popover [scroll-margin-top:15px] max-[760px]:hidden max-[760px]:p-[15px]"
          :class="{ 'max-[760px]:flex': settingsOpen }"
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
            class="w-full p-[clamp(15px,1.15vw,23px)] bg-[rgb(255_255_255/12%)] dark:bg-[rgb(28_33_23/12%)] border border-border-subtle rounded-[clamp(24px,1.75vw,35px)] max-[760px]:p-[15px] max-[760px]:rounded-[27px]"
            @generate="generateImages"
            @enhance="enhancePrompt"
          />
        </aside>
      </div>
    </section>

    <AppToasts />
  </main>
</template>
