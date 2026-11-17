<script setup>
import { Head, Link, usePoll } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import AppIcon from '../components/AppIcon.vue';
import DashboardShell from '../components/dashboard/DashboardShell.vue';
import GenerationCard from '../components/dashboard/GenerationCard.vue';
import { Button } from '../components/ui/button';
import { notify } from '../lib/toast';

const props = defineProps({
    generations: {
        type: Array,
        default: () => [],
    },
});

const search = ref('');
const statusFilter = ref('all');

const statusFilters = [
    { value: 'all', label: 'All' },
    { value: 'succeeded', label: 'Completed' },
    { value: 'pending', label: 'In Progress' },
    { value: 'failed', label: 'Failed' },
];

const hasPendingGenerations = computed(() => props.generations.some((generation) => ['queued', 'processing'].includes(generation.status)));

const { start, stop } = usePoll(2000, {
    only: ['generations', 'balance_nd', 'balance_display'],
}, {
    autoStart: false,
});

const filteredGenerations = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.generations.filter((generation) => {
        if (statusFilter.value === 'succeeded' && generation.status !== 'succeeded') {
            return false;
        }

        if (statusFilter.value === 'pending' && !['queued', 'processing'].includes(generation.status)) {
            return false;
        }

        if (statusFilter.value === 'failed' && generation.status !== 'failed') {
            return false;
        }

        if (!query) {
            return true;
        }

        return generation.prompt?.toLowerCase().includes(query);
    });
});

watch(hasPendingGenerations, (pending, wasPending) => {
    if (pending) {
        start();
        return;
    }

    stop();

    if (wasPending) {
        notify('Your generation results are ready.');
    }
}, { immediate: true });
</script>

<template>
  <Head title="Dashboard — intellect" />

  <DashboardShell
    v-model:search="search"
    :has-pending-generations="hasPendingGenerations"
  >
    <section
      class="flex min-h-0 min-w-0 flex-1 flex-col pt-[clamp(20px,1.7vw,36px)] px-[clamp(20px,1.55vw,32px)] pb-[clamp(18px,1.35vw,28px)] bg-[radial-gradient(circle_at_0%_0%,rgb(32_90_65/22%),transparent_40%),radial-gradient(circle_at_75%_65%,rgb(90_79_12/12%),transparent_50%)] max-[1300px]:pt-6 max-[1300px]:px-5 max-[1300px]:pb-5 max-[760px]:min-h-auto max-[760px]:pt-[25px] max-[760px]:px-[17px] max-[760px]:pb-6"
      aria-labelledby="all-generations-title"
    >
      <div class="flex shrink-0 flex-wrap items-center justify-between gap-4 pb-4 border-b border-border-subtle">
        <div>
          <h1
            id="all-generations-title"
            class="m-0 text-[clamp(27px,1.65vw,34px)] font-medium tracking-[-0.04em]"
          >
            All Generations
          </h1>
          <p class="m-0 mt-1 text-muted-foreground text-[clamp(12px,0.8vw,14px)]">
            {{ props.generations.length > 0 ? `${props.generations.length} ${props.generations.length === 1 ? 'creation' : 'creations'} in your library` : 'Your generated images will appear here' }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <span
            v-if="hasPendingGenerations"
            role="status"
            class="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/12 px-3 py-1.5 text-xs font-medium text-foreground"
          >
            <span class="size-2 rounded-full bg-primary animate-pulse" />
            Generating…
          </span>
          <Link
            href="/dashboard/generate"
            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground no-underline shadow-[0_12px_28px_rgb(255_193_0/10%)] transition-[background-color,transform] duration-200 hover:-translate-y-0.5 hover:bg-primary-hover"
          >
            <AppIcon
              name="plus"
              :size="17"
            />
            Generate Image
          </Link>
        </div>
      </div>

      <div
        v-if="props.generations.length > 0"
        class="flex shrink-0 flex-wrap items-center gap-2 pt-4"
        role="group"
        aria-label="Filter generations by status"
      >
        <Button
          v-for="filter in statusFilters"
          :key="filter.value"
          variant="chip"
          size="none"
          class="min-h-8 px-3.5 py-1.5 text-xs"
          :class="statusFilter === filter.value ? 'bg-primary text-primary-foreground border-transparent font-medium hover:bg-primary-hover dark:hover:bg-primary-hover' : ''"
          @click="statusFilter = filter.value"
        >
          {{ filter.label }}
        </Button>
      </div>

      <div
        class="grid min-h-0 flex-1 content-start overflow-x-hidden overflow-y-auto [scrollbar-width:thin] [scrollbar-color:var(--secondary)_transparent] grid-cols-[repeat(4,minmax(0,1fr))] gap-[clamp(14px,1.4vw,24px)] pt-[clamp(16px,1.5vw,24px)] pb-[clamp(24px,2vw,36px)] px-[2px] max-[1300px]:grid-cols-3 max-[900px]:grid-cols-2 max-[760px]:gap-[14px] max-[760px]:py-[22px] max-[760px]:px-0 max-[760px]:overflow-visible"
        :class="{ 'flex flex-col items-center justify-center gap-3': filteredGenerations.length === 0 }"
        aria-live="polite"
        :aria-busy="hasPendingGenerations"
      >
        <template v-if="props.generations.length === 0">
          <p class="m-0 text-center text-muted-foreground">
            You haven’t generated any images yet.
          </p>
          <Link
            href="/dashboard/generate"
            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-full border border-border-subtle bg-secondary px-5 py-2 text-sm text-foreground no-underline hover:bg-white/38 dark:hover:bg-white/13"
          >
            <AppIcon
              name="wand"
              :size="17"
            />
            Generate your first image
          </Link>
        </template>
        <template v-else-if="filteredGenerations.length === 0">
          <p class="m-0 text-center text-muted-foreground">
            No generations match the current filter.
          </p>
          <Button
            variant="chip"
            size="none"
            @click="search = ''; statusFilter = 'all'"
          >
            Reset filters
          </Button>
        </template>
        <GenerationCard
          v-for="generation in filteredGenerations"
          :key="generation.id"
          :generation="generation"
        />
      </div>
    </section>
  </DashboardShell>
</template>
