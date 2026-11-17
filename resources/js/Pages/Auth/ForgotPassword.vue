<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AuthLayout from '../../components/AuthLayout.vue';
import { Button } from '../../components/ui/button';
import { TextField } from '../../components/ui/textfield';

const form = useForm({
    email: '',
});
const page = usePage();
const status = computed(() => page.props.flash?.status);
</script>

<template>
  <AuthLayout
    title="Forgot your password?"
    description="Enter your email address and we will send you a secure reset link."
  >
    <Head title="Forgot password" />

    <div class="flex flex-col gap-5">
      <div
        v-if="status"
        class="rounded-xl border border-emerald-300/20 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-200"
        role="status"
      >
        {{ status }}
      </div>

      <form
        class="flex flex-col gap-5"
        @submit.prevent="form.post('/forgot-password')"
      >
        <TextField
          name="email"
          :invalid="form.errors.email ? true : undefined"
        >
          <TextField.Label>Email address</TextField.Label>
          <TextField.Group>
            <TextField.Input
              v-model="form.email"
              type="email"
              autocomplete="email"
              placeholder="you@example.com"
              required
              @value-change="form.clearErrors('email')"
            />
          </TextField.Group>
          <TextField.Error match="valueMissing">Enter your email address.</TextField.Error>
          <TextField.Error match="typeMismatch">Enter a valid email address.</TextField.Error>
          <TextField.Error
            v-if="form.errors.email"
            match
          >
            {{ form.errors.email }}
          </TextField.Error>
        </TextField>

        <Button
          class="w-full"
          size="lg"
          type="submit"
          :disabled="form.processing"
          :aria-busy="form.processing"
          focusable-when-disabled
        >
          Send reset link
        </Button>
      </form>

      <p class="text-center text-sm text-white/70">
        <Link
          href="/login"
          class="font-medium text-accent underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          Back to sign in
        </Link>
      </p>
    </div>
  </AuthLayout>
</template>
