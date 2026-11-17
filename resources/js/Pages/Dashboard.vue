<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AuthLayout from '../components/AuthLayout.vue';
import { Button } from '../components/ui/button';

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
  <AuthLayout
    title="Your workspace"
    description="Your account is verified and ready for thoughtful prompt creation."
  >
    <Head title="Dashboard" />

    <div class="flex flex-col gap-5">
      <p class="text-white/70">
        Signed in as <strong class="text-white">{{ user?.name }}</strong>
      </p>
      <Button
        class="w-full"
        size="lg"
        @click="router.post('/logout')"
      >
        Log out
      </Button>
      <p class="text-center text-sm text-white/70">
        <Link
          href="/"
          class="font-medium text-accent underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          Back to home
        </Link>
      </p>
    </div>
  </AuthLayout>
</template>
