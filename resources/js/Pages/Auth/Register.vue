<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

import AuthLayout from '../../components/AuthLayout.vue';
import { Button } from '../../components/ui/button';
import { Password } from '../../components/ui/password';
import { TextField } from '../../components/ui/textfield';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        onError: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
  <AuthLayout
    title="Create your account"
    description="Start building thoughtful prompts and vibrant AI creations."
  >
    <Head title="Create account" />

    <form
      class="flex flex-col gap-5"
      @submit.prevent="submit"
    >
      <TextField
        name="name"
        :invalid="form.errors.name ? true : undefined"
      >
        <TextField.Label>Name</TextField.Label>
        <TextField.Group>
          <TextField.Input
            v-model="form.name"
            autocomplete="name"
            placeholder="Your name"
            required
            @value-change="form.clearErrors('name')"
          />
        </TextField.Group>
        <TextField.Error match="valueMissing">
          Enter your name.
        </TextField.Error>
        <TextField.Error
          v-if="form.errors.name"
          match
        >
          {{ form.errors.name }}
        </TextField.Error>
      </TextField>

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
          autocomplete="new-password"
          required
          @value-change="form.clearErrors('password')"
        >
          <Password.Input
            placeholder="At least 8 characters"
            minlength="8"
          />
          <Password.Toggle />
        </Password.Group>
        <Password.Error match="valueMissing">
          Choose a password.
        </Password.Error>
        <Password.Error match="tooShort">
          Use at least 8 characters.
        </Password.Error>
        <Password.Error
          v-if="form.errors.password"
          match
        >
          {{ form.errors.password }}
        </Password.Error>
      </Password>

      <Password
        name="password_confirmation"
        :invalid="form.errors.password_confirmation ? true : undefined"
      >
        <Password.Label>Confirm password</Password.Label>
        <Password.Group
          v-model="form.password_confirmation"
          autocomplete="new-password"
          required
          @value-change="form.clearErrors('password_confirmation')"
        >
          <Password.Input
            placeholder="Repeat your password"
            minlength="8"
          />
          <Password.Toggle />
        </Password.Group>
        <Password.Error match="valueMissing">
          Confirm your password.
        </Password.Error>
        <Password.Error match="tooShort">
          Use at least 8 characters.
        </Password.Error>
        <Password.Error
          v-if="form.errors.password_confirmation"
          match
        >
          {{ form.errors.password_confirmation }}
        </Password.Error>
      </Password>

      <Button
        class="w-full"
        size="lg"
        type="submit"
        :disabled="form.processing"
        :aria-busy="form.processing"
        focusable-when-disabled
      >
        Create account
      </Button>

      <p class="flex flex-wrap justify-center gap-1 text-sm text-white/70">
        Already have an account?
        <Link
          href="/login"
          class="font-medium text-accent underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          Sign in
        </Link>
      </p>
    </form>
  </AuthLayout>
</template>
