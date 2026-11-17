<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

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
  <main class="min-h-[100svh] overflow-x-clip bg-[#15180f] text-[#f3f4e9]">
    <Head title="intellect — turn words into images" />

    <header class="mx-auto flex w-full max-w-6xl items-center justify-between gap-6 px-5 py-6 sm:px-8">
      <Link
        href="/"
        class="inline-flex items-center gap-2.5 text-lg font-medium tracking-[-0.03em] no-underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
        aria-label="intellect home"
      >
        <svg
          class="size-7 text-accent"
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
            class="inline-flex min-h-11 items-center justify-center rounded-full bg-accent px-4 font-semibold text-accent-foreground no-underline transition-transform duration-200 hover:-translate-y-0.5 hover:bg-accent-hover focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent active:translate-y-0 sm:px-5"
          >
            Open the studio
          </Link>
          <button
            type="button"
            class="min-h-11 rounded-full px-2 text-[#b8bdaf] underline-offset-4 hover:text-[#f3f4e9] hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent sm:px-3"
            @click="logout"
          >
            Log out
          </button>
        </template>
        <template v-else>
          <Link
            href="/login"
            class="inline-flex min-h-11 items-center rounded-full px-1 text-[#b8bdaf] no-underline underline-offset-4 hover:text-[#f3f4e9] hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent sm:px-2"
          >
            Sign in
          </Link>
          <Link
            href="/register"
            class="inline-flex min-h-11 items-center justify-center rounded-full bg-accent px-4 font-semibold text-accent-foreground no-underline transition-transform duration-200 hover:-translate-y-0.5 hover:bg-accent-hover focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent active:translate-y-0 sm:px-5"
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
        <div class="fade-up flex flex-col items-start gap-6">
          <p class="text-sm font-medium tracking-[0.18em] text-[#c9cfba] uppercase">
            AI image studio
          </p>
          <h1
            id="hero-title"
            class="text-[2.9rem] leading-[0.98] font-medium tracking-[-0.045em] text-balance sm:text-6xl"
          >
            Describe it. Watch it become an image.
          </h1>
          <p class="max-w-xl text-lg leading-relaxed text-[#c0c3b5] sm:text-xl">
            intellect turns a plain-language prompt into finished artwork. Set your canvas, pick a
            quality, add up to four reference images — and see exactly what it costs in credits
            before anything is generated.
          </p>
          <div class="mt-1 flex flex-wrap items-center gap-5">
            <Link
              :href="user ? '/dashboard' : '/register'"
              class="inline-flex min-h-12 items-center justify-center gap-3 rounded-full bg-accent px-7 text-base font-semibold text-accent-foreground no-underline transition-transform duration-200 hover:-translate-y-0.5 hover:bg-accent-hover focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent active:translate-y-0"
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
            <p class="text-sm text-[#b8bdaf]">
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
            class="tile"
            :style="{ '--tilt': item.tilt }"
          >
            <figure
              class="fade-up m-0"
              :style="{ '--fade-delay': item.delay }"
            >
              <img
                :src="item.src"
                :alt="item.prompt"
                class="aspect-square w-full rounded-[24%] border-2 border-[#e8ecd8]/25 object-cover shadow-[0_18px_40px_rgb(0_0_0/35%)]"
                width="512"
                height="512"
                loading="lazy"
              >
              <figcaption class="mt-3 line-clamp-2 text-xs leading-5 text-[#b8bdaf] sm:text-sm">
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
            class="rounded-3xl border border-white/8 bg-white/[0.05] p-6"
          >
            <h3 class="text-lg font-medium tracking-[-0.02em]">
              {{ feature.title }}
            </h3>
            <p class="mt-2 text-base leading-7 text-[#c0c3b5]">
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
      <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-[#c0c3b5]">
        Create an account, grab a few credits, and start painting with words.
      </p>
      <Link
        :href="user ? '/dashboard' : '/register'"
        class="mt-8 inline-flex min-h-12 items-center justify-center gap-3 rounded-full bg-accent px-8 text-base font-semibold text-accent-foreground no-underline transition-transform duration-200 hover:-translate-y-0.5 hover:bg-accent-hover focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent active:translate-y-0"
      >
        {{ user ? 'Open your studio' : 'Get started' }}
      </Link>
    </section>

    <footer class="border-t border-white/8 px-5 py-8 sm:px-8">
      <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-4 text-sm text-[#8e937f]">
        <p>intellect — image generation powered by PixelForge.</p>
        <nav
          class="flex items-center gap-6"
          aria-label="Footer"
        >
          <template v-if="user">
            <Link
              href="/dashboard"
              class="rounded-sm underline-offset-4 hover:text-[#f3f4e9] hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
            >
              Dashboard
            </Link>
            <button
              type="button"
              class="rounded-sm underline-offset-4 hover:text-[#f3f4e9] hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
              @click="logout"
            >
              Log out
            </button>
          </template>
          <template v-else>
            <Link
              href="/login"
              class="rounded-sm underline-offset-4 hover:text-[#f3f4e9] hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
            >
              Sign in
            </Link>
            <Link
              href="/register"
              class="rounded-sm underline-offset-4 hover:text-[#f3f4e9] hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
            >
              Create account
            </Link>
          </template>
        </nav>
      </div>
    </footer>
  </main>
</template>

<style scoped>
.fade-up {
  animation: fade-up 600ms cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: var(--fade-delay, 0ms);
}

@keyframes fade-up {
  from {
    opacity: 0;
    transform: translateY(16px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (min-width: 640px) {
  .tile {
    transform: rotate(var(--tilt, 0deg));
    transition: transform 300ms cubic-bezier(0.2, 0.8, 0.2, 1);
  }

  .tile:hover,
  .tile:focus-within {
    transform: rotate(0deg) translateY(-6px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .fade-up {
    animation: none;
  }

  .tile {
    transform: none;
    transition: none;
  }
}
</style>
