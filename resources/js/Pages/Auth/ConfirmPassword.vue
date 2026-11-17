<script setup>
import { Head, useForm } from '@inertiajs/vue3';

import AuthLayout from '../../components/AuthLayout.vue';
import { Button } from '../../components/ui/button';
import { Password } from '../../components/ui/password';

const form = useForm({
    password: '',
});

function submit() {
    form.post('/confirm-password', {
        onError: () => form.reset('password'),
    });
}
</script>

<template>
  <AuthLayout
    title="Confirm your password"
    description="For your security, confirm your password before entering this protected area."
  >
    <Head title="Confirm password" />

    <form
      class="flex flex-col gap-5"
      @submit.prevent="submit"
    >
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
          <Password.Input placeholder="Your current password" />
          <Password.Toggle />
        </Password.Group>
        <Password.Error match="valueMissing">Enter your password.</Password.Error>
        <Password.Error
          v-if="form.errors.password"
          match
        >
          {{ form.errors.password }}
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
        Confirm password
      </Button>
    </form>
  </AuthLayout>
</template>
