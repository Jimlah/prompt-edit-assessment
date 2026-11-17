<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Input, InputClear, InputGroup } from '@ui-sdk/vue/input';
import { Menu, MenuTrigger } from '@ui-sdk/vue/menu';
import { computed, ref } from 'vue';

import AppIcon from '../AppIcon.vue';
import AppToasts from '../AppToasts.vue';
import DashboardSidebar from './DashboardSidebar.vue';
import { Button } from '../ui/button';
import { MenuContent, MenuItem, MenuItemIcon } from '../ui/menu';
import { useTheme } from '../../composables/useTheme';
import { notify } from '../../lib/toast';

defineProps({
    hasPendingGenerations: {
        type: Boolean,
        default: false,
    },
    showSettingsButton: {
        type: Boolean,
        default: false,
    },
    showSearch: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['toggle-settings']);

const search = defineModel('search', { type: String, default: '' });

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userInitial = computed(() => user.value?.name?.trim()?.charAt(0)?.toUpperCase() || 'I');
const balanceDisplay = computed(() => page.props.balance_display ?? null);

const mobileOpen = ref(false);
const { dark, toggle: toggleTheme } = useTheme();

function buyCredits(amount) {
    router.post('/dashboard/credits', { amount }, {
        preserveScroll: true,
        onSuccess: () => notify(`${amount} credits added to your balance.`),
        onError: () => notify('The purchase could not be completed.', 'error'),
    });
}

function handleSettings() {
    mobileOpen.value = false;
    emit('toggle-settings');
}

function logout() {
    router.post('/logout');
}
</script>

<template>
  <main
    class="relative flex min-h-[100svh] isolate overflow-x-clip text-foreground bg-ambience bg-[radial-gradient(circle_at_13%_25%,rgb(232_220_130/65%),transparent_31%),radial-gradient(circle_at_74%_42%,rgb(148_190_161/58%),transparent_38%)] dark:bg-[radial-gradient(circle_at_13%_25%,rgb(146_154_72/40%),transparent_29%),radial-gradient(circle_at_74%_42%,rgb(12_72_58/43%),transparent_36%),radial-gradient(circle_at_67%_95%,rgb(108_94_14/32%),transparent_35%)]"
  >
    <div
      class="pointer-events-none fixed -inset-20 -z-10 bg-[radial-gradient(ellipse_at_23%_35%,rgb(202_183_66/16%),transparent_27%),radial-gradient(ellipse_at_70%_20%,rgb(0_79_62/22%),transparent_35%)] blur-[55px]"
      aria-hidden="true"
    />

    <section
      class="grid w-full min-h-[100svh] m-auto grid-cols-[clamp(230px,18vw,325px)_minmax(0,1fr)] bg-card max-[1300px]:grid-cols-[230px_minmax(0,1fr)] max-[1100px]:grid-cols-[minmax(0,1fr)]"
      aria-label="Intellect AI image generation dashboard"
    >
      <DashboardSidebar
        class="sticky top-0 h-[100svh]"
        :mobile-open="mobileOpen"
        :is-admin="user?.is_admin"
        :balance-display="balanceDisplay"
        @close="mobileOpen = false"
        @buy="buyCredits"
        @settings="handleSettings"
      />
      <Button
        v-if="mobileOpen"
        variant="circle"
        size="none"
        class="hidden max-[1100px]:fixed max-[1100px]:inset-0 max-[1100px]:z-50 max-[1100px]:block max-[1100px]:cursor-pointer bg-[rgb(5_10_7/55%)] border-0 backdrop-blur-[5px] hover:bg-[rgb(5_10_7/55%)] dark:hover:bg-[rgb(5_10_7/55%)]"
        aria-label="Close navigation"
        @click="mobileOpen = false"
      />

      <div class="col-start-2 flex h-[100svh] min-w-0 flex-col max-[1100px]:col-start-1 max-[760px]:h-auto max-[760px]:min-h-svh">
        <header class="sticky top-0 z-20 flex shrink-0 min-w-0 items-center justify-between gap-6 px-[clamp(20px,1.55vw,32px)] py-[clamp(10px,0.85vw,16px)] bg-topbar border-b border-border backdrop-blur-[28px] max-[760px]:gap-2.5 max-[760px]:min-h-[74px] max-[760px]:py-2.5 max-[760px]:px-3.5">
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
            v-if="showSearch"
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
              placeholder="Search…"
              aria-label="Search creations"
              class="w-full min-w-0 bg-transparent border-0 outline-none text-[clamp(15px,0.95vw,19px)] placeholder:text-foreground/80 [&::-webkit-search-cancel-button]:hidden max-[760px]:text-sm"
            />
            <InputClear
              v-show="search"
              class="mr-1 inline-grid size-6 shrink-0 cursor-pointer place-items-center rounded-md text-muted-foreground hover:text-foreground"
              aria-label="Clear search"
            >
              <AppIcon
                name="close"
                :size="16"
              />
            </InputClear>
          </InputGroup>
          <div class="ml-auto flex items-center gap-[clamp(7px,0.55vw,11px)] max-[760px]:gap-[7px]">
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
              v-if="showSettingsButton"
              variant="circle"
              size="none"
              class="max-[760px]:size-[42px] max-[390px]:hidden"
              aria-label="Open generation settings"
              @click="handleSettings"
            >
              <AppIcon
                name="settings"
                :size="23"
              />
            </Button>
            <Button
              variant="circle"
              size="none"
              class="max-[760px]:size-[42px]"
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

        <slot />
      </div>
    </section>

    <AppToasts />
  </main>
</template>
