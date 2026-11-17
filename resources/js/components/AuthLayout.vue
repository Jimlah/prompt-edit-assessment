<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
});
</script>

<template>
  <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[oklch(0.19_0.025_110)] px-4 py-10 text-white sm:px-6">
    <div
      class="pointer-events-none absolute inset-0"
      aria-hidden="true"
    >
      <div class="absolute -top-24 -left-24 size-96 rounded-full bg-accent/15 blur-3xl" />
      <div class="absolute right-0 bottom-0 size-112 rounded-full bg-emerald-700/20 blur-3xl" />
    </div>

    <section
      class="relative flex w-full max-w-md flex-col gap-8 rounded-[2rem] border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur-2xl sm:p-8"
      aria-labelledby="auth-title"
    >
      <Link
        href="/"
        class="inline-flex w-fit items-center gap-2 rounded-lg text-lg font-semibold tracking-tight focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
        aria-label="Prompt Edit Assessment home"
      >
        <svg
          class="size-7 text-accent"
          viewBox="0 0 32 32"
          fill="currentColor"
          aria-hidden="true"
        >
          <path d="M13 2h6l1.5 6.2L26 6l4 5-4.5 5 4.5 5-4 5-5.5-2.2L19 30h-6l-1.5-6.2L6 26l-4-5 4.5-5L2 11l4-5 5.5 2.2L13 2Z" />
        </svg>
        <span>prompt edit</span>
      </Link>

      <div class="flex flex-col gap-2">
        <h1
          id="auth-title"
          class="text-3xl font-semibold tracking-tight"
        >
          {{ title }}
        </h1>
        <p class="leading-6 text-white/70">
          {{ description }}
        </p>
      </div>

      <div>
        <slot />
      </div>
    </section>
  </main>
</template>
