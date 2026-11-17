<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

import AppIcon from '../AppIcon.vue';
import { Button } from '../ui/button';

const props = defineProps({
    mobileOpen: {
        type: Boolean,
        default: false,
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
    balanceDisplay: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'buy', 'settings']);

const navigation = computed(() => [
    { label: 'Home', icon: 'home', href: '/' },
    { label: 'Image Generator', icon: 'image', active: true },
    { label: 'Settings', icon: 'settings', action: 'settings' },
    ...(props.isAdmin ? [{ label: 'Admin Report', icon: 'chart', href: '/admin/report' }] : []),
]);

const creditPacks = [5, 10, 20];

function handleNavigation(item) {
    if (item.action) {
        emit(item.action);
        return;
    }

    if (item.active) {
        emit('close');
    }
}
</script>

<template>
  <aside
    class="relative z-20 flex min-h-0 row-start-1 row-end-3 flex-col pt-[clamp(22px,1.7vw,34px)] px-[clamp(18px,1.55vw,30px)] pb-[clamp(20px,1.55vw,30px)] text-foreground bg-sidebar border-r border-border backdrop-blur-[28px] motion-reduce:transition-none max-[1100px]:fixed max-[1100px]:inset-y-0 max-[1100px]:left-0 max-[1100px]:z-60 max-[1100px]:w-[min(330px,88vw)] max-[1100px]:p-6 max-[1100px]:bg-[#d5d8b7]/95 dark:max-[1100px]:bg-[#59634f] max-[1100px]:shadow-[24px_0_80px_rgb(0_0_0/35%)] max-[1100px]:-translate-x-[105%] max-[1100px]:transition-transform max-[1100px]:duration-[240ms] max-[1100px]:ease-[ease] max-[1100px]:motion-reduce:transition-none"
    :class="{ 'max-[1100px]:translate-x-0': mobileOpen }"
    aria-label="Primary navigation"
  >
    <div class="flex items-center justify-between">
      <Link
        href="/dashboard"
        class="inline-flex items-center gap-[11px] text-foreground text-[clamp(25px,1.65vw,34px)] font-semibold tracking-[-0.045em] no-underline focus-visible:outline-2 focus-visible:outline-offset-3 focus-visible:outline-ring"
        aria-label="Intellect image generator"
      >
        <svg
          class="size-9 text-primary drop-shadow-[0_0_14px_rgb(255_209_0/20%)]"
          viewBox="0 0 40 40"
          fill="currentColor"
          aria-hidden="true"
        >
          <path d="M17 2h6l1.3 7.1L30 5l5 5-4.1 5.7L38 17v6l-7.1 1.3L35 30l-5 5-5.7-4.1L23 38h-6l-1.3-7.1L10 35l-5-5 4.1-5.7L2 23v-6l7.1-1.3L5 10l5-5 5.7 4.1Z" />
        </svg>
        <span>intellect</span>
      </Link>
      <Button
        variant="ghost"
        size="none"
        class="size-[42px] rounded-full p-0 hidden max-[1100px]:inline-grid"
        aria-label="Close navigation"
        @click="emit('close')"
      >
        <AppIcon
          name="close"
          :size="21"
        />
      </Button>
    </div>

    <nav class="flex flex-col gap-[clamp(6px,0.45vw,10px)] mt-[clamp(44px,3.3vw,66px)] max-[1100px]:gap-[7px] max-[1100px]:mt-[38px]">
      <component
        :is="item.href ? Link : 'button'"
        v-for="item in navigation"
        :key="item.label"
        :href="item.href"
        type="button"
        class="relative flex w-full min-h-[clamp(44px,2.7vw,54px)] items-center gap-3.5 px-3 py-[9px] text-foreground text-[clamp(15px,0.95vw,19px)] text-left no-underline cursor-pointer bg-transparent border-0 rounded-xl transition-[color,background-color,transform] duration-[180ms] ease-[ease] hover:bg-foreground/10 hover:translate-x-0.5 focus-visible:outline-2 focus-visible:outline-offset-3 focus-visible:outline-ring motion-reduce:transition-none max-[1100px]:min-h-12 max-[1100px]:text-base"
        :class="{ 'text-primary bg-[linear-gradient(90deg,rgb(255_216_0/14%),transparent_85%)]': item.active }"
        :aria-current="item.active ? 'page' : undefined"
        @click="handleNavigation(item)"
      >
        <span
          v-if="item.active"
          class="absolute top-[5%] bottom-[5%] left-[clamp(-30px,-1.55vw,-18px)] w-[7px] bg-primary rounded-r-lg shadow-[0_0_28px_7px_rgb(255_216_0/25%)] max-[1100px]:left-[-24px]"
          aria-hidden="true"
        />
        <AppIcon
          :name="item.icon"
          :size="22"
          class="shrink-0"
        />
        <span>{{ item.label }}</span>
      </component>
    </nav>

    <section
      class="mt-auto p-[clamp(18px,1.3vw,26px)] bg-secondary border border-border rounded-[28px] shadow-[inset_0_1px_0_rgb(255_255_255/5%)] max-[1100px]:mt-8"
      aria-labelledby="credits-title"
    >
      <div class="flex items-center gap-2.5">
        <AppIcon
          name="coins"
          :size="23"
        />
        <h2 id="credits-title" class="m-0 text-[clamp(18px,1.15vw,23px)] font-medium tracking-[-0.025em]">
          Credits
        </h2>
      </div>
      <p class="mt-[13px] mb-5 text-muted-foreground text-[clamp(14px,0.83vw,17px)] leading-[1.5]">
        Generate images for <strong data-testid="balance" class="text-foreground tabular-nums">{{ balanceDisplay }}</strong>. Credits are US dollars.
      </p>
      <div class="flex flex-col gap-2">
        <Button
          v-for="pack in creditPacks"
          :key="pack"
          variant="primary"
          size="none"
          class="w-full min-h-11 px-3.5 py-2.5 text-sm rounded-[13px] shadow-[0_9px_24px_rgb(255_193_0/12%)]"
          :aria-label="`Buy ${pack} dollars of credits`"
          @click="emit('buy', pack)"
        >
          Buy ${{ pack }}
        </Button>
      </div>
    </section>
  </aside>
</template>

