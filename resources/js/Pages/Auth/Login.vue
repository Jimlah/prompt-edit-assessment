<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AuthLayout from '../../components/AuthLayout.vue';
import { Button } from '../../components/ui/button';
import { Checkbox } from '../../components/ui/checkbox';
import { Password } from '../../components/ui/password';
import { TextField } from '../../components/ui/textfield';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
const page = usePage();
const status = computed(() => page.props.flash?.status);

function submit() {
    form.post('/login', {
        onError: () => form.reset('password'),
    });
}
</script>

<template>
  <AuthLayout
    title="Welcome back"
    description="Sign in to continue shaping prompts and creating with AI."
  >
    <Head title="Sign in" />

    <div
      v-if="status"
      class="mb-5 rounded-xl border border-emerald-300/20 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-200"
      role="status"
    >
      {{ status }}
    </div>

    <form
      class="flex flex-col gap-5"
      @submit.prevent="submit"
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
            autocomplete="username"
            placeholder="you@example.com"
            required
            @value-change="form.clearErrors('email')"
          />
        </TextField.Group>
        <TextField.Error match="valueMissing">
          Enter your email address.
        </TextField.Error>
        <TextField.Error match="typeMismatch">
          Enter a valid email address.
        </TextField.Error>
        <TextField.Error
          v-if="form.errors.email"
          match
        >
          {{ form.errors.email }}
        </TextField.Error>
      </TextField>

      <Password
        name="password"
        :invalid="form.errors.password ? true : undefined"
      >
        <Password.Label>Password</Password.Label>
        <Password.Group
          v-model="form.password"
          autocomplete="current-password"
          required
          @value-change="form.clearErrors('password')"
        >
          <Password.Input placeholder="Your password" />
          <Password.Toggle />
        </Password.Group>
        <Password.Error match="valueMissing">
          Enter your password.
        </Password.Error>
        <Password.Error
          v-if="form.errors.password"
          match
        >
          {{ form.errors.password }}
        </Password.Error>
      </Password>

      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-h-10 items-center gap-2 text-sm text-white/80">
          <Checkbox
            v-model:checked="form.remember"
            name="remember"
            aria-labelledby="remember-label"
          >
            <Checkbox.Indicator />
          </Checkbox>
          <span id="remember-label">Remember me</span>
        </div>
        <Link
          href="/forgot-password"
          class="rounded-lg text-sm font-medium text-accent underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          Forgot password?
        </Link>
      </div>

      <Button
        class="w-full"
        size="lg"
        type="submit"
        :disabled="form.processing"
        :aria-busy="form.processing"
        focusable-when-disabled
      >
        Sign in
      </Button>

      <p class="flex flex-wrap justify-center gap-1 text-sm text-white/70">
        New to Prompt Edit?
        <Link
          href="/register"
          class="font-medium text-accent underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          Create an account
        </Link>
      </p>
    </form>
  </AuthLayout>
</template>
