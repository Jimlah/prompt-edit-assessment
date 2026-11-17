<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

import AppIcon from '../AppIcon.vue';

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

const emit = defineEmits(['close', 'unavailable', 'buy', 'settings']);

const navigation = computed(() => [
    { label: 'Home', icon: 'home', href: '/' },
    { label: 'Community Feed', icon: 'users' },
    { label: 'Community Details', icon: 'community' },
    { label: 'AI Chat Bot', icon: 'chat' },
    { label: 'Image Generator', icon: 'image', active: true },
    { label: 'Voice Generate', icon: 'voice' },
    { label: 'Register', icon: 'register' },
    { label: 'Settings', icon: 'settings', action: 'settings' },
    ...(props.isAdmin ? [{ label: 'Admin Report', icon: 'chart', href: '/admin/report' }] : []),
]);

const creditPacks = [5, 10, 20];

function handleNavigation(item) {
    if (item.action) {
        emit(item.action);
        return;
    }

    if (!item.href && !item.active) {
        emit('unavailable', item.label);
        return;
    }

    if (item.active) {
        emit('close');
    }
}
</script>

<template>
  <aside
    class="sidebar"
    :class="{ 'is-open': mobileOpen }"
    aria-label="Primary navigation"
  >
    <div class="brand-row">
      <Link
        href="/dashboard"
        class="brand"
        aria-label="Intellect image generator"
      >
        <svg
          class="brand-mark"
          viewBox="0 0 40 40"
          fill="currentColor"
          aria-hidden="true"
        >
          <path d="M17 2h6l1.3 7.1L30 5l5 5-4.1 5.7L38 17v6l-7.1 1.3L35 30l-5 5-5.7-4.1L23 38h-6l-1.3-7.1L10 35l-5-5 4.1-5.7L2 23v-6l7.1-1.3L5 10l5-5 5.7 4.1Z" />
        </svg>
        <span>intellect</span>
      </Link>
      <button
        type="button"
        class="icon-button close-button"
        aria-label="Close navigation"
        @click="emit('close')"
      >
        <AppIcon
          name="close"
          :size="21"
        />
      </button>
    </div>

    <nav class="navigation">
      <component
        :is="item.href ? Link : 'button'"
        v-for="item in navigation"
        :key="item.label"
        :href="item.href"
        type="button"
        class="navigation-item"
        :class="{ active: item.active }"
        :aria-current="item.active ? 'page' : undefined"
        @click="handleNavigation(item)"
      >
        <AppIcon
          :name="item.icon"
          :size="22"
        />
        <span>{{ item.label }}</span>
      </component>
    </nav>

    <section
      class="upgrade-card"
      aria-labelledby="credits-title"
    >
      <div class="upgrade-heading">
        <AppIcon
          name="coins"
          :size="23"
        />
        <h2 id="credits-title">
          Credits
        </h2>
      </div>
      <p>Generate images for <strong data-testid="balance">{{ balanceDisplay }}</strong>. Credits are US dollars.</p>
      <div class="credit-buttons">
        <button
          v-for="pack in creditPacks"
          :key="pack"
          type="button"
          class="upgrade-button credit-button"
          aria-label="Buy 5 dollars of credits"
          @click="emit('buy', pack)"
        >
          Buy ${{ pack }}
        </button>
      </div>
    </section>
  </aside>
</template>

<style scoped>
.sidebar {
  position: relative;
  z-index: 20;
  display: flex;
  min-height: 0;
  grid-row: 1 / 3;
  flex-direction: column;
  padding: clamp(22px, 1.7vw, 34px) clamp(18px, 1.55vw, 30px) clamp(20px, 1.55vw, 30px);
  color: var(--text);
  background: var(--sidebar);
  border-right: 1px solid var(--border);
  backdrop-filter: blur(28px);
}

.brand-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 11px;
  color: var(--text);
  font-size: clamp(25px, 1.65vw, 34px);
  font-weight: 600;
  letter-spacing: -0.045em;
  text-decoration: none;
}

.brand-mark {
  width: 36px;
  height: 36px;
  color: var(--accent);
  filter: drop-shadow(0 0 14px rgb(255 209 0 / 20%));
}

.icon-button {
  display: inline-grid;
  width: 42px;
  height: 42px;
  place-items: center;
  color: var(--text);
  cursor: pointer;
  background: transparent;
  border: 0;
  border-radius: 50%;
}

.close-button {
  display: none;
}

.navigation {
  display: flex;
  flex-direction: column;
  gap: clamp(6px, 0.45vw, 10px);
  margin-top: clamp(44px, 3.3vw, 66px);
}

.navigation-item {
  position: relative;
  display: flex;
  width: 100%;
  min-height: clamp(44px, 2.7vw, 54px);
  align-items: center;
  gap: 14px;
  padding: 9px 12px;
  color: var(--text);
  font: inherit;
  font-size: clamp(15px, 0.95vw, 19px);
  text-align: left;
  text-decoration: none;
  cursor: pointer;
  background: transparent;
  border: 0;
  border-radius: 12px;
  transition: color 180ms ease, background 180ms ease, transform 180ms ease;
}

.navigation-item:hover {
  background: rgb(255 255 255 / 8%);
  transform: translateX(2px);
}

.navigation-item.active {
  color: var(--accent);
  background: linear-gradient(90deg, rgb(255 216 0 / 14%), transparent 85%);
}

.navigation-item.active::before {
  position: absolute;
  top: 5%;
  bottom: 5%;
  left: clamp(-30px, -1.55vw, -18px);
  width: 7px;
  content: '';
  background: var(--accent);
  border-radius: 0 8px 8px 0;
  box-shadow: 0 0 28px 7px rgb(255 216 0 / 25%);
}

.navigation-item svg {
  flex: 0 0 auto;
}

.upgrade-card {
  padding: clamp(18px, 1.3vw, 26px);
  margin-top: auto;
  background: var(--control);
  border: 1px solid var(--border);
  border-radius: 28px;
  box-shadow: inset 0 1px 0 rgb(255 255 255 / 5%);
}

.upgrade-heading {
  display: flex;
  align-items: center;
  gap: 10px;
}

.upgrade-heading h2 {
  margin: 0;
  font-size: clamp(18px, 1.15vw, 23px);
  font-weight: 500;
  letter-spacing: -0.025em;
}

.upgrade-card p {
  margin: 13px 0 20px;
  color: var(--muted);
  font-size: clamp(14px, 0.83vw, 17px);
  line-height: 1.5;
}

.upgrade-button {
  width: 100%;
  min-height: 50px;
  padding: 12px 16px;
  color: #2c290f;
  font: inherit;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  background: var(--accent);
  border: 0;
  border-radius: 15px;
  box-shadow: 0 9px 24px rgb(255 193 0 / 12%);
  transition: transform 180ms ease, background 180ms ease;
}

.upgrade-button:hover {
  background: var(--accent-hover);
  transform: translateY(-2px);
}

.credit-buttons {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.credit-button {
  min-height: 44px;
  padding: 10px 14px;
  font-size: 14px;
  border-radius: 13px;
}

.upgrade-card strong {
  color: var(--text);
  font-variant-numeric: tabular-nums;
}

.navigation-item:focus-visible,
.upgrade-button:focus-visible,
.brand:focus-visible,
.icon-button:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 3px;
}

@media (max-width: 1100px) {
  .sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 60;
    width: min(330px, 88vw);
    padding: 24px;
    background: #59634f;
    box-shadow: 24px 0 80px rgb(0 0 0 / 35%);
    transform: translateX(-105%);
    transition: transform 240ms ease;
  }

  .sidebar.is-open {
    transform: translateX(0);
  }

  .close-button {
    display: inline-grid;
  }

  .navigation {
    gap: 7px;
    margin-top: 38px;
  }

  .navigation-item {
    min-height: 48px;
    font-size: 16px;
  }

  .navigation-item.active::before {
    left: -24px;
  }

  .upgrade-card {
    margin-top: 32px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .navigation-item,
  .upgrade-button,
  .sidebar {
    transition: none;
  }
}
</style>
