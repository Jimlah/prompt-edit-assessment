<script setup>
import { Head, Link, usePoll } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

import AppIcon from '../../components/AppIcon.vue';
import DashboardShell from '../../components/dashboard/DashboardShell.vue';
import GenerationCard from '../../components/dashboard/GenerationCard.vue';
import GeneratorSettings from '../../components/dashboard/GeneratorSettings.vue';
import { Button } from '../../components/ui/button';
import { notify } from '../../lib/toast';

const props = defineProps({
    generations: {
        type: Array,
        default: () => [],
    },
});

const settingsOpen = ref(false);

const activeGeneration = computed(() => props.generations[0] ?? null);
const isPending = computed(() => ['queued', 'processing'].includes(activeGeneration.value?.status));

const { start, stop } = usePoll(2000, {
    only: ['generations', 'balance_nd', 'balance_display'],
}, {
    autoStart: false,
});

watch(isPending, (pending, wasPending) => {
    if (pending) {
        start();
        return;
    }

    stop();

    if (wasPending) {
        notify('Your generation result is ready.');
    }
}, { immediate: true });

function openSettings() {
    settingsOpen.value = !settingsOpen.value;

    if (window.innerWidth > 760) {
        nextTick(() => document.querySelector('#image-prompt')?.focus());
        return;
    }

    nextTick(() => document.querySelector('#generation-settings')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}
</script>

<template>
  <Head title="Image Generator — intellect" />

  <DashboardShell
    :has-pending-generations="isPending"
    :show-settings-button="true"
    :show-search="false"
    @toggle-settings="openSettings"
  >
    <div class="grid min-h-0 min-w-0 flex-1 grid-cols-[minmax(0,1fr)_clamp(315px,22.5vw,440px)] max-[1300px]:grid-cols-[minmax(0,1fr)_315px] max-[760px]:min-h-0 max-[760px]:block">
      <section
        class="flex min-h-0 min-w-0 flex-col pt-[clamp(20px,1.7vw,36px)] px-[clamp(20px,1.55vw,32px)] pb-[clamp(18px,1.35vw,28px)] bg-[radial-gradient(circle_at_0%_0%,rgb(32_90_65/22%),transparent_40%),radial-gradient(circle_at_75%_65%,rgb(90_79_12/12%),transparent_50%)] min-[1700px]:px-[30px] max-[1300px]:pt-6 max-[1300px]:px-5 max-[1300px]:pb-5 max-[760px]:min-h-auto max-[760px]:pt-[25px] max-[760px]:px-[17px] max-[760px]:pb-6"
        aria-labelledby="generator-title"
      >
        <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 pb-4 border-b border-border-subtle">
          <div>
            <h1
              id="generator-title"
              class="m-0 text-[clamp(27px,1.65vw,34px)] font-medium tracking-[-0.04em]"
            >
              Generate Image
            </h1>
            <p class="m-0 mt-1 text-muted-foreground text-[clamp(12px,0.8vw,14px)]">
              {{ isPending ? 'Creating your image — updating automatically' : 'Your generated image appears below' }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <span
              v-if="isPending"
              role="status"
              class="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/12 px-3 py-1.5 text-xs font-medium text-foreground"
            >
              <span class="size-2 rounded-full bg-primary animate-pulse" />
              {{ activeGeneration?.status === 'processing' ? 'Generating…' : 'Queued…' }}
            </span>
            <Link
              href="/dashboard"
              class="inline-flex min-h-9 items-center gap-1.5 rounded-full border border-border-subtle bg-secondary px-3.5 py-1.5 text-xs font-medium text-foreground no-underline hover:bg-white/38 dark:hover:bg-white/13"
            >
              All Generations
            </Link>
            <Button
              variant="chip"
              size="none"
              class="hidden max-[760px]:inline-flex"
              @click="openSettings"
            >
              <AppIcon
                name="wand"
                :size="16"
              />
              {{ settingsOpen ? 'Hide Studio' : 'New Image' }}
            </Button>
          </div>
        </div>

        <div
          class="flex min-h-0 flex-1 items-center justify-center py-[clamp(20px,2vw,36px)]"
          aria-live="polite"
          :aria-busy="isPending"
        >
          <GenerationCard
            v-if="activeGeneration"
            :generation="activeGeneration"
            class="w-full max-w-[min(100%,460px)]"
          />
          <div
            v-else
            class="flex w-full max-w-[min(100%,460px)] aspect-square flex-col items-center justify-center gap-3 rounded-[24px] border border-dashed border-border bg-secondary/60 p-8 text-center"
          >
            <div class="grid size-14 place-items-center rounded-2xl bg-primary/15 text-foreground dark:text-primary">
              <AppIcon
                name="wand"
                :size="26"
              />
            </div>
            <p class="m-0 text-base font-medium text-foreground">
              Ready to create
            </p>
            <p class="m-0 max-w-[280px] text-xs leading-[1.5] text-muted-foreground">
              Describe your image in the studio panel and click Generate to start.
            </p>
          </div>
        </div>
      </section>

      <aside
        id="generation-settings"
        class="flex min-h-0 min-w-0 overflow-y-auto p-[clamp(18px,1.55vw,31px)] bg-popover [scrollbar-width:thin] [scrollbar-color:var(--secondary)_transparent] [scroll-margin-top:15px] max-[760px]:overflow-visible max-[760px]:p-[15px]"
        :class="settingsOpen ? 'max-[760px]:flex' : 'max-[760px]:hidden'"
        aria-label="Generation controls"
      >
        <GeneratorSettings
          :initial-prompt="activeGeneration?.prompt"
          class="w-full p-[clamp(15px,1.15vw,23px)] bg-[rgb(255_255_255/12%)] dark:bg-[rgb(28_33_23/12%)] border border-border-subtle rounded-[clamp(24px,1.75vw,35px)] max-[760px]:p-[15px] max-[760px]:rounded-[27px]"
        />
      </aside>
    </div>
  </DashboardShell>
</template>
