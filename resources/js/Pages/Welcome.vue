<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import { Button } from '../components/ui/button';
import { Textarea } from '../components/ui/textarea';

const examplePrompts = [
    'Write a clear project brief for a small design studio.',
    'Help me turn a rough idea into a focused creative direction.',
];

const prompt = ref('');
const isVerified = ref(false);
const exampleIndex = ref(0);
const page = usePage();
const user = computed(() => page.props.auth?.user);
const wordCount = computed(() => {
    const trimmedPrompt = prompt.value.trim();

    return trimmedPrompt ? trimmedPrompt.split(/\s+/).length : 0;
});
const heroPrompt = computed(() => prompt.value.trim() || examplePrompts[exampleIndex.value]);
const heroPromptKey = computed(() => prompt.value.trim() ? 'current-prompt' : exampleIndex.value);

let exampleTimer;
let revealObserver;
let motionPreference;

function logout() {
    router.post('/logout');
}

function verifyPrompt() {
    isVerified.value = true;
}

function goToEditor() {
    const editor = document.querySelector('#editor');

    editor?.scrollIntoView({
        behavior: motionPreference?.matches ? 'auto' : 'smooth',
        block: 'start',
    });
    document.querySelector('#prompt-input')?.focus({ preventScroll: true });
}

function startExampleRotation() {
    window.clearInterval(exampleTimer);

    if (motionPreference?.matches) {
        exampleIndex.value = 0;

        return;
    }

    exampleTimer = window.setInterval(() => {
        if (document.visibilityState === 'visible' && !prompt.value.trim()) {
            exampleIndex.value = (exampleIndex.value + 1) % examplePrompts.length;
        }
    }, 6000);
}

function handleMotionPreferenceChange() {
    startExampleRotation();
}

onMounted(() => {
    motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
    motionPreference.addEventListener('change', handleMotionPreferenceChange);
    startExampleRotation();

    document.documentElement.classList.add('motion-ready');
    revealObserver = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        }
    }, {
        rootMargin: '0px',
        threshold: 0.05,
    });

    document.querySelectorAll('[data-reveal]').forEach((element) => {
        revealObserver.observe(element);
    });
});

onBeforeUnmount(() => {
    window.clearInterval(exampleTimer);
    revealObserver?.disconnect();
    motionPreference?.removeEventListener('change', handleMotionPreferenceChange);
    document.documentElement.classList.remove('motion-ready');
});
</script>

<template>
  <main class="motion-page overflow-x-clip bg-[#141712] text-[#f1f3e9]">
    <Head title="Prompt Edit — A better prompt starts here" />

    <section
      class="relative flex min-h-[100svh] flex-col px-5 pt-24 pb-8 sm:px-8"
      aria-labelledby="hero-title"
    >
      <header
        class="absolute inset-x-0 top-0 flex h-24 items-center justify-between gap-5 px-5 sm:px-8"
        data-reveal
      >
        <Link
          href="/"
          class="inline-flex shrink-0 items-center gap-2.5 rounded-full text-lg no-underline font-medium tracking-[-0.03em] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
          aria-label="Prompt Edit home"
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

        <nav
          class="hidden items-center gap-7 text-base text-[#b8bdaf] md:flex"
          aria-label="Main navigation"
        >
          <a
            href="#editor"
            class="rounded-sm no-underline underline-offset-4 transition-colors hover:text-[#f1f3e9] hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
          >Editor</a>
          <a
            href="#details"
            class="rounded-sm no-underline underline-offset-4 transition-colors hover:text-[#f1f3e9] hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
          >Details</a>
        </nav>

        <div class="flex items-center justify-end gap-3 sm:gap-5">
          <template v-if="user">
            <span class="hidden max-w-36 truncate text-sm text-[#b8bdaf] sm:inline">{{ user.name }}</span>
            <button
              type="button"
              class="min-h-11 rounded-full px-2 text-sm font-medium underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
              @click="logout"
            >
              Log out
            </button>
          </template>
          <template v-else>
            <Link
              href="/login"
              class="inline-flex min-h-11 items-center rounded-full px-1 text-sm font-medium no-underline underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent sm:px-2"
            >
              Sign in
            </Link>
            <Link
              href="/register"
              class="inline-flex min-h-11 items-center justify-center rounded-full bg-accent no-underline px-4 text-sm font-semibold text-accent-foreground transition-transform duration-200 hover:-translate-y-0.5 hover:bg-accent-hover focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent active:translate-y-0 sm:px-5"
            >
              Create account
            </Link>
          </template>
        </div>
      </header>

      <div class="mx-auto flex w-full max-w-[100rem] flex-1 flex-col items-center justify-center gap-5 py-6 sm:gap-6">
        <div
          data-ui
          data-reveal
          class="flex aspect-[4/5] w-[min(78vw,19rem)] flex-col gap-5 rounded-[28px] bg-[#e8ebdf] p-5 text-[#1d211a] transition-transform duration-500 hover:-translate-y-2 sm:w-76 sm:p-6"
        >
          <div class="flex items-center justify-between gap-4 text-sm font-medium">
            <span>Prompt draft</span>
            <svg
              class="size-5"
              viewBox="0 0 32 32"
              fill="currentColor"
              aria-hidden="true"
            >
              <path d="M13 2h6l1.5 6.2L26 6l4 5-4.5 5 4.5 5-4 5-5.5-2.2L19 30h-6l-1.5-6.2L6 26l-4-5 4.5-5L2 11l4-5 5.5 2.2L13 2Z" />
            </svg>
          </div>

          <div class="relative min-h-0 flex-1">
            <Transition name="prompt-crossfade">
              <p
                :key="heroPromptKey"
                class="absolute inset-0 line-clamp-6 text-xl leading-[1.35] tracking-[-0.025em] sm:text-2xl"
              >
                {{ heroPrompt }}
              </p>
            </Transition>
          </div>

          <div class="flex items-center justify-between gap-4 text-sm tabular-nums">
            <span>{{ heroPrompt.length }} / 500</span>
            <span class="font-medium">Live draft</span>
          </div>
        </div>

        <h1
          id="hero-title"
          class="whitespace-nowrap text-[14vw] leading-[0.82] font-medium tracking-[-0.065em]"
          data-reveal
          style="--delay: 70ms"
        >
          prompt edit
        </h1>

        <p
          class="max-w-2xl text-center text-2xl leading-[1.15] tracking-[-0.035em] text-balance text-[#d7dbcf] sm:text-3xl lg:text-4xl"
          data-reveal
          style="--delay: 140ms"
        >
          A focused editor for turning rough thoughts into clear prompts.
        </p>

        <div
          class="flex flex-col items-center gap-4 pt-2 text-sm text-[#b8bdaf] sm:flex-row sm:gap-10 sm:pt-4"
          data-reveal
          style="--delay: 210ms"
        >
          <span>500 characters</span>
          <button
            type="button"
            class="inline-flex min-h-12 items-center justify-center gap-3 rounded-full bg-accent px-7 text-base font-semibold text-accent-foreground transition-transform duration-200 hover:-translate-y-0.5 hover:bg-accent-hover focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent active:translate-y-0"
            @click="goToEditor"
          >
            Start writing
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
          </button>
          <span>⌘/Ctrl + Enter</span>
        </div>
      </div>
    </section>

    <section
      id="editor"
      class="relative scroll-mt-0 lg:grid lg:min-h-[90svh] lg:grid-cols-2"
      aria-labelledby="editor-title"
    >
      <h2
        id="editor-title"
        class="sr-only"
      >
        From rough to ready
      </h2>

      <div class="relative flex min-h-[62svh] flex-col justify-center px-5 py-20 sm:px-8 lg:min-h-[90svh] lg:px-[clamp(2rem,5vw,5rem)] lg:py-36">
        <form
          data-ui
          data-reveal
          class="mx-auto flex w-full max-w-[34rem] flex-col gap-5"
          @submit.prevent="verifyPrompt"
        >
          <Textarea name="prompt">
            <Textarea.Label class="text-base">Write your prompt</Textarea.Label>
            <Textarea.Group class="rounded-[28px] border-white/20 bg-white/[0.06] p-5 shadow-none sm:p-6">
              <Textarea.Input
                id="prompt-input"
                v-model="prompt"
                class="max-h-80 min-h-48 text-lg leading-7 sm:text-xl sm:leading-8"
                placeholder="Describe what you want to create…"
                rows="5"
                :maxlength="500"
                auto-resize
                submit-shortcut="mod+enter"
                required
                @value-change="isVerified = false"
              />
              <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-[#c3c8bb]">
                <span class="tabular-nums">{{ prompt.length }} / 500</span>
                <span aria-hidden="true">⌘/Ctrl + Enter</span>
              </div>
            </Textarea.Group>
            <Textarea.Error match="valueMissing">
              Enter a prompt.
            </Textarea.Error>
          </Textarea>

          <div class="flex flex-wrap items-center gap-5">
            <Button
              class="rounded-full shadow-none"
              size="lg"
              type="submit"
            >
              Verify component
            </Button>
            <p
              class="min-h-6 max-w-xs text-sm leading-6 text-[#c3c8bb]"
              role="status"
            >
              {{ isVerified ? 'The editor is working. Your draft is still here.' : '' }}
            </p>
          </div>
        </form>

        <p
          class="mt-auto pt-16 text-[clamp(2.5rem,5vw,5rem)] leading-none font-medium tracking-[-0.055em] lg:absolute lg:bottom-8 lg:left-8 lg:pt-0"
          aria-hidden="true"
          data-reveal
        >
          From rough
        </p>
      </div>

      <div class="relative flex min-h-[62svh] flex-col bg-[#e8ebdf] px-5 py-20 text-[#1d211a] sm:px-8 lg:min-h-[90svh] lg:px-[clamp(2rem,5vw,5rem)] lg:py-36">
        <p
          class="mb-auto pb-16 text-[clamp(2.5rem,5vw,5rem)] leading-none font-medium tracking-[-0.055em] lg:absolute lg:right-8 lg:bottom-8 lg:mb-0 lg:pb-0"
          aria-hidden="true"
          data-reveal
        >
          to ready
        </p>

        <div
          data-ui
          data-reveal
          class="mx-auto my-auto flex min-h-72 w-full max-w-[34rem] flex-col justify-center gap-8 py-8"
          style="--delay: 90ms"
        >
          <div class="flex items-center justify-between gap-5">
            <h3 class="text-base font-semibold">Live preview</h3>
            <span class="text-sm tabular-nums">{{ wordCount }} {{ wordCount === 1 ? 'word' : 'words' }}</span>
          </div>

          <p
            class="text-2xl leading-[1.45] tracking-[-0.025em] break-words whitespace-pre-wrap text-pretty"
            :class="prompt.trim() ? 'text-[#1d211a]' : 'text-[#62695c]'"
          >
            {{ prompt.trim() || 'Your prompt will take shape here as you write.' }}
          </p>

          <p class="max-w-md text-base leading-7 text-[#505749]">
            This preview stays in your browser. Edit on the left and see your words settle into place.
          </p>
        </div>
      </div>
    </section>

    <section
      id="details"
      class="relative min-h-[100svh] scroll-mt-0 px-5 py-28 sm:px-8 lg:flex lg:items-center lg:py-32"
      aria-labelledby="details-title"
    >
      <div class="relative mx-auto w-full max-w-7xl">
        <h2
          id="details-title"
          class="mb-12 max-w-sm text-4xl leading-[1.02] font-medium tracking-[-0.05em] text-balance lg:absolute lg:top-0 lg:left-0 lg:mb-0 lg:w-[29%] lg:text-right"
          data-reveal
        >
          Stay in the thought
        </h2>

        <div
          data-ui
          data-reveal
          class="mx-auto flex min-h-[580px] w-full max-w-[400px] flex-col gap-6 rounded-[28px] bg-[#e8ebdf] p-6 text-[#1d211a] sm:min-h-[640px] sm:p-7"
        >
          <div class="flex items-center justify-between gap-4">
            <span class="text-base font-semibold">Prompt</span>
            <svg
              class="size-6"
              viewBox="0 0 32 32"
              fill="currentColor"
              aria-hidden="true"
            >
              <path d="M13 2h6l1.5 6.2L26 6l4 5-4.5 5 4.5 5-4 5-5.5-2.2L19 30h-6l-1.5-6.2L6 26l-4-5 4.5-5L2 11l4-5 5.5 2.2L13 2Z" />
            </svg>
          </div>

          <div class="flex min-h-56 flex-1 flex-col justify-between gap-8 rounded-[20px] bg-[#d7dbc9] p-5">
            <p class="line-clamp-6 text-xl leading-[1.45] tracking-[-0.025em] break-words">
              {{ heroPrompt }}
            </p>
            <span class="self-end text-sm tabular-nums">{{ heroPrompt.length }} / 500</span>
          </div>

          <div class="flex items-center justify-between gap-4">
            <span class="text-sm text-[#505749]">Submit from the keyboard</span>
            <div class="flex items-center gap-2 text-sm font-semibold">
              <kbd class="rounded-lg bg-[#1d211a] px-3 py-2 font-sans text-[#f1f3e9]">⌘ / Ctrl</kbd>
              <kbd class="rounded-lg bg-[#1d211a] px-3 py-2 font-sans text-[#f1f3e9]">Enter</kbd>
            </div>
          </div>

          <div class="rounded-[20px] bg-[#d7dbc9] p-5">
            <p class="text-base leading-6 font-medium">
              {{ isVerified ? 'Component verified. Your draft is still here.' : 'Validation and feedback appear right where you need them.' }}
            </p>
          </div>

          <button
            type="button"
            class="inline-flex min-h-11 items-center justify-center rounded-full text-sm font-semibold underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1d211a]"
            @click="goToEditor"
          >
            Open the editor
          </button>
        </div>

        <article
          class="mt-12 max-w-xs lg:absolute lg:top-[20%] lg:left-0 lg:mt-0 lg:w-[25%] lg:text-right"
          data-reveal
        >
          <h3 class="text-lg font-medium">Room for the whole thought</h3>
          <p class="mt-2 text-base leading-7 text-[#b8bdaf]">
            The textarea grows as you write, so revising never means working in a cramped box.
          </p>
        </article>

        <article
          class="mt-8 max-w-xs lg:absolute lg:bottom-[8%] lg:left-0 lg:mt-0 lg:w-[25%] lg:text-right"
          data-reveal
          style="--delay: 70ms"
        >
          <h3 class="text-lg font-medium">A count without the clutter</h3>
          <p class="mt-2 text-base leading-7 text-[#b8bdaf]">
            See your character count as you go, with a clear 500-character limit.
          </p>
        </article>

        <article
          class="mt-12 max-w-xs lg:absolute lg:top-[28%] lg:right-0 lg:mt-0 lg:w-[25%]"
          data-reveal
          style="--delay: 140ms"
        >
          <h3 class="text-lg font-medium">Your hands stay on the keys</h3>
          <p class="mt-2 text-base leading-7 text-[#b8bdaf]">
            Use Command or Control + Enter to submit without reaching for the pointer.
          </p>
        </article>

        <article
          class="mt-8 max-w-xs lg:absolute lg:right-0 lg:bottom-[16%] lg:mt-0 lg:w-[25%]"
          data-reveal
          style="--delay: 210ms"
        >
          <h3 class="text-lg font-medium">Feedback in context</h3>
          <p class="mt-2 text-base leading-7 text-[#b8bdaf]">
            Required-field validation and verification feedback stay attached to the editor.
          </p>
        </article>
      </div>
    </section>

    <footer class="flex min-h-[100svh] flex-col bg-[#e8ebdf] px-5 pt-12 pb-5 text-[#1d211a] sm:px-8">
      <div class="grid grid-cols-2 gap-x-6 gap-y-10 sm:grid-cols-4">
        <div data-reveal>
          <p class="font-semibold">Prompt Edit</p>
          <p class="mt-3 max-w-48 text-base leading-7 text-[#505749]">
            A focused place to shape what you want to say.
          </p>
        </div>

        <nav
          aria-label="Footer navigation"
          data-reveal
          style="--delay: 70ms"
        >
          <p class="font-semibold">Navigate</p>
          <div class="mt-3 flex flex-col items-start gap-2 text-base text-[#505749]">
            <a
              href="#editor"
              class="rounded-sm text-[#505749] no-underline underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1d211a]"
            >Editor</a>
            <a
              href="#details"
              class="rounded-sm text-[#505749] no-underline underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1d211a]"
            >Details</a>
          </div>
        </nav>

        <div
          data-reveal
          style="--delay: 140ms"
        >
          <p class="font-semibold">Account</p>
          <div class="mt-3 flex flex-col items-start gap-2 text-base text-[#505749]">
            <template v-if="user">
              <span class="max-w-full truncate">{{ user.name }}</span>
              <button
                type="button"
                class="rounded-sm text-[#505749] no-underline underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1d211a]"
                @click="logout"
              >
                Log out
              </button>
            </template>
            <template v-else>
              <Link
                href="/login"
                class="rounded-sm text-[#505749] no-underline underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1d211a]"
              >Sign in</Link>
              <Link
                href="/register"
                class="rounded-sm text-[#505749] no-underline underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1d211a]"
              >Create account</Link>
            </template>
          </div>
        </div>

        <div
          data-reveal
          style="--delay: 210ms"
        >
          <p class="font-semibold">Built with</p>
          <ul class="mt-3 space-y-2 text-base text-[#505749]">
            <li>Laravel + PostgreSQL</li>
            <li>Inertia + Vue</li>
            <li>Tailwind + UI SDK</li>
          </ul>
        </div>
      </div>

      <div class="flex flex-1 items-end pt-[20svh]">
        <div
          class="w-full"
          data-reveal
        >
          <p
            class="text-[25vw] leading-[0.78] font-medium tracking-[-0.075em] sm:hidden"
            aria-label="Prompt Edit"
          >
            prompt<br>edit
          </p>
          <p
            class="hidden whitespace-nowrap text-[13.4vw] leading-[0.78] font-medium tracking-[-0.075em] sm:block"
            aria-hidden="true"
          >
            prompt edit
          </p>
          <p class="mt-6 text-sm text-[#505749]">
            Prompt Edit Assessment. Write clearly, keep your train of thought.
          </p>
        </div>
      </div>
    </footer>
  </main>
</template>

<style>
.prompt-crossfade-enter-active,
.prompt-crossfade-leave-active {
    transition: opacity 1.6s ease, transform 1.6s ease;
}

.prompt-crossfade-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.prompt-crossfade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.motion-ready [data-reveal] {
    opacity: 0;
    transform: translateY(24px);
}

.motion-ready [data-reveal].is-visible {
    animation: section-enter 800ms cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-delay: var(--delay, 0ms);
}

@keyframes section-enter {
    from {
        opacity: 0;
        transform: translateY(24px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .prompt-crossfade-enter-active,
    .prompt-crossfade-leave-active {
        transition: none;
    }

    .motion-ready [data-reveal],
    .motion-ready [data-reveal].is-visible {
        opacity: 1;
        transform: none;
        animation: none;
    }
}
</style>
