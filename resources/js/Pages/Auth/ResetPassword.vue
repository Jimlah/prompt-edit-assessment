<script setup>
import { Head, useForm } from '@inertiajs/vue3';

import AuthLayout from '../../components/AuthLayout.vue';
import { Button } from '../../components/ui/button';
import { Password } from '../../components/ui/password';
import { TextField } from '../../components/ui/textfield';

const props = defineProps({
    token: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password', {
        onError: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
  <AuthLayout
    title="Reset your password"
    description="Choose a new password for your account."
  >
    <Head title="Reset password" />

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

      <Password
        name="password"
        :invalid="form.errors.password ? true : undefined"
      >
        <Password.Label>New password</Password.Label>
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
        <Password.Error match="valueMissing">Choose a new password.</Password.Error>
        <Password.Error match="tooShort">Use at least 8 characters.</Password.Error>
        <Password.Error
          v-if="form.errors.password"
          match
        >
          {{ form.errors.password }}
        </Password.Error>
      </Password>

      <Password name="password_confirmation">
        <Password.Label>Confirm new password</Password.Label>
        <Password.Group
          v-model="form.password_confirmation"
          autocomplete="new-password"
          required
        >
          <Password.Input
            placeholder="Repeat your new password"
            minlength="8"
          />
          <Password.Toggle />
        </Password.Group>
        <Password.Error match="valueMissing">Confirm your new password.</Password.Error>
        <Password.Error match="tooShort">Use at least 8 characters.</Password.Error>
      </Password>

      <Button
        class="w-full"
        size="lg"
        type="submit"
        :disabled="form.processing"
        :aria-busy="form.processing"
        focusable-when-disabled
      >
        Reset password
      </Button>
    </form>
  </AuthLayout>
</template>
