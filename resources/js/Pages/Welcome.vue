<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { buttonVariants } from '../components/ui/button';
import { cn } from '../lib/utils';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const headerCtaClasses = computed(() => cn(
    buttonVariants({ variant: 'primary' }),
    'rounded-full px-4 no-underline sm:px-5',
));
const headerLinkClasses = computed(() => cn(
    buttonVariants({ variant: 'ghost' }),
    'min-h-11 rounded-full px-1 font-normal no-underline underline-offset-4 hover:underline sm:px-2',
));
const ghostPillClasses = computed(() => cn(
    buttonVariants({ variant: 'ghost' }),
    'min-h-11 rounded-full px-2 font-normal underline-offset-4 hover:underline sm:px-3',
));
const heroCtaClasses = computed(() => cn(
    buttonVariants({ variant: 'primary', size: 'lg' }),
    'rounded-full px-7 no-underline',
));
const textButtonClasses = computed(() => cn(
    buttonVariants({ variant: 'ghost' }),
    'h-auto rounded-sm px-0 text-sm font-normal underline-offset-4 hover:underline',
));

const showcase = [
    {
        src: '/images/showcase/cyberpunk.webp',
        prompt: 'Cyberpunk android portrait with luminous circuitry and a neon city bokeh.',
        tilt: '-4deg',
        delay: '120ms',
    },
    {
        src: '/images/showcase/beetle.webp',
        prompt: 'An ornate mechanical beetle resting on a moss-covered stone, macro 3D render.',
        tilt: '1deg',
        delay: '200ms',
    },
    {
        src: '/images/showcase/lantern.webp',
        prompt: 'A glass lantern filled with a glowing tree and cyan butterflies in a moonlit forest.',
        tilt: '4deg',
        delay: '280ms',
    },
];

const features = [
    {
        title: 'Know the price first',
        body: 'A live estimate in credits follows every edit — the 25% service fee already included. Only finished images are charged.',
    },
    {
        title: 'Set the canvas',
        body: 'Square, wide or tall, in low, medium or high quality. Add up to four reference-image URLs to steer style and subject.',
    },
    {
        title: 'Watch the gallery fill',
        body: 'Queue an image and your gallery updates itself while PixelForge paints. Every prompt, cost and result stays searchable.',
    },
];

function logout() {
    router.post('/logout');
}
</script>

<template>
  <main class="min-h-[100svh] overflow-x-clip bg-background text-foreground">
    <Head title="intellect — turn words into images" />

    <header class="mx-auto flex w-full max-w-6xl items-center justify-between gap-6 px-5 py-6 sm:px-8">
      <Link
        href="/"
        class="inline-flex items-center gap-2.5 text-lg font-medium tracking-[-0.03em] no-underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
        aria-label="intellect home"
      >
        <svg
          class="size-7 text-primary"
          viewBox="0 0 40 40"
          fill="currentColor"
          aria-hidden="true"
        >
          <path d="M17 2h6l1.3 7.1L30 5l5 5-4.1 5.7L38 17v6l-7.1 1.3L35 30l-5 5-5.7-4.1L23 38h-6l-1.3-7.1L10 35l-5-5 4.1-5.7L2 23v-6l7.1-1.3L5 10l5-5 5.7 4.1Z" />
        </svg>
        <span>intellect</span>
      </Link>

      <div class="flex items-center gap-3 text-sm sm:gap-5">
        <template v-if="user">
          <Link
            href="/dashboard"
            :class="headerCtaClasses"
          >
            Open the studio
          </Link>
          <button
            type="button"
            :class="ghostPillClasses"
            @click="logout"
          >
            Log out
          </button>
        </template>
        <template v-else>
          <Link
            href="/login"
            :class="headerLinkClasses"
          >
            Sign in
          </Link>
          <Link
            href="/register"
            :class="headerCtaClasses"
          >
            Create account
          </Link>
        </template>
      </div>
    </header>

    <section
      class="relative px-5 sm:px-8"
      aria-labelledby="hero-title"
    >
      <div class="mx-auto grid w-full max-w-6xl items-center gap-12 py-10 sm:py-16 lg:grid-cols-[1.05fr_1fr] lg:gap-14 lg:py-20">
        <div class="flex flex-col items-start gap-6 animate-fade-up [animation-delay:var(--fade-delay,0ms)] motion-reduce:animate-none">
          <p class="text-sm font-medium tracking-[0.18em] text-muted-foreground uppercase">
            AI image studio
          </p>
          <h1
            id="hero-title"
            class="text-[2.9rem] leading-[0.98] font-medium tracking-[-0.045em] text-balance sm:text-6xl"
          >
            Describe it. Watch it become an image.
          </h1>
          <p class="max-w-xl text-lg leading-relaxed text-muted-foreground sm:text-xl">
            intellect turns a plain-language prompt into finished artwork. Set your canvas, pick a
            quality, add up to four reference images — and see exactly what it costs in credits
            before anything is generated.
          </p>
          <div class="mt-1 flex flex-wrap items-center gap-5">
            <Link
              :href="user ? '/dashboard' : '/register'"
              :class="heroCtaClasses"
            >
              {{ user ? 'Open your studio' : 'Start creating' }}
              <svg
                class="size-4"
                viewBox="0 0 16 16"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                aria-hidden="true"
              >
                <path d="M3 8h10M9 4l4 4-4 4" />
              </svg>
            </Link>
            <p class="text-sm text-muted-foreground">
              Failed runs cost nothing
            </p>
          </div>
        </div>

        <ul
          class="grid grid-cols-3 gap-3 sm:gap-5"
          aria-label="Images made in the studio"
        >
          <li
            v-for="item in showcase"
            :key="item.src"
            class="sm:[rotate:var(--tilt,0deg)] sm:transition-transform sm:duration-300 sm:[transition-timing-function:cubic-bezier(0.2,0.8,0.2,1)] sm:hover:[rotate:0deg] sm:hover:-translate-y-1.5 sm:focus-within:[rotate:0deg] sm:focus-within:-translate-y-1.5 motion-reduce:[rotate:0deg] motion-reduce:transition-none"
            :style="{ '--tilt': item.tilt }"
          >
            <figure
              class="m-0 animate-fade-up [animation-delay:var(--fade-delay,0ms)] motion-reduce:animate-none"
              :style="{ '--fade-delay': item.delay }"
            >
              <img
                :src="item.src"
                :alt="item.prompt"
                class="aspect-square w-full rounded-[24%] border-2 border-foreground/20 object-cover shadow-[0_18px_40px_rgb(0_0_0/35%)]"
                width="512"
                height="512"
                loading="lazy"
              >
              <figcaption class="mt-3 line-clamp-2 text-xs leading-5 text-muted-foreground sm:text-sm">
                {{ item.prompt }}
              </figcaption>
            </figure>
          </li>
        </ul>
      </div>
    </section>

    <section
      class="px-5 pb-10 sm:px-8"
      aria-labelledby="features-title"
    >
      <div class="mx-auto w-full max-w-6xl">
        <h2
          id="features-title"
          class="sr-only"
        >
          What is inside the studio
        </h2>
        <div class="grid gap-4 sm:grid-cols-3">
          <article
            v-for="feature in features"
            :key="feature.title"
            class="rounded-3xl border border-border bg-secondary p-6"
          >
            <h3 class="text-lg font-medium tracking-[-0.02em]">
              {{ feature.title }}
            </h3>
            <p class="mt-2 text-base leading-7 text-muted-foreground">
              {{ feature.body }}
            </p>
          </article>
        </div>
      </div>
    </section>

    <section
      class="relative px-5 py-20 text-center sm:px-8"
      aria-labelledby="cta-title"
    >
      <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_center,rgb(255_212_0/9%),transparent_65%)]" />
      <h2
        id="cta-title"
        class="mx-auto max-w-2xl text-3xl leading-tight font-medium tracking-[-0.04em] text-balance sm:text-5xl"
      >
        Your first image is one prompt away
      </h2>
      <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-muted-foreground">
        Create an account, grab a few credits, and start painting with words.
      </p>
      <Link
        :href="user ? '/dashboard' : '/register'"
        :class="heroCtaClasses"
      >
        {{ user ? 'Open your studio' : 'Get started' }}
      </Link>
    </section>

    <footer class="border-t border-border px-5 py-8 sm:px-8">
      <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-4 text-sm text-muted-foreground">
        <p>intellect — image generation powered by PixelForge.</p>
        <nav
          class="flex items-center gap-6"
          aria-label="Footer"
        >
          <template v-if="user">
            <Link
              href="/dashboard"
              class="rounded-sm underline-offset-4 hover:text-foreground hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
            >
              Dashboard
            </Link>
            <button
              type="button"
              :class="textButtonClasses"
              @click="logout"
            >
              Log out
            </button>
          </template>
          <template v-else>
            <Link
              href="/login"
              class="rounded-sm underline-offset-4 hover:text-foreground hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
            >
              Sign in
            </Link>
            <Link
              href="/register"
              class="rounded-sm underline-offset-4 hover:text-foreground hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
            >
              Create account
            </Link>
          </template>
        </nav>
      </div>
    </footer>
  </main>
</template>

