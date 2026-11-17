<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AuthLayout from '../../components/AuthLayout.vue';
import { Button } from '../../components/ui/button';

const form = useForm({});
const page = usePage();
const status = computed(() => page.props.flash?.status);
</script>

<template>
  <AuthLayout
    title="Verify your email"
    description="Check your inbox for a verification link before entering your workspace."
  >
    <Head title="Verify email" />

    <div class="flex flex-col gap-5">
      <div
        v-if="status === 'verification-link-sent'"
        class="rounded-xl border border-emerald-300/20 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-200"
        role="status"
      >
        A new verification link has been emailed to you.
      </div>

      <Button
        class="w-full"
        size="lg"
        :disabled="form.processing"
        :aria-busy="form.processing"
        focusable-when-disabled
        @click="form.post('/email/verification-notification')"
      >
        Resend verification email
      </Button>

      <p class="text-center text-sm text-white/70">
        <Link
          href="/logout"
          method="post"
          as="button"
          class="rounded-lg px-2 font-medium text-accent underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          Log out
        </Link>
      </p>
    </div>
  </AuthLayout>
</template>
