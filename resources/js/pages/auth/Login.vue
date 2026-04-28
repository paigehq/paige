<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-[#F7F5FF]">
    <div class="w-full max-w-sm rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
      <h1 class="mb-6 text-2xl font-bold text-[#1A0B3B]">
        Sign in to Paige
      </h1>
      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500"
          >
          <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">
            {{ form.errors.email }}
          </p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500"
          >
          <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
            {{ form.errors.password }}
          </p>
        </div>
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-50"
        >
          Sign in
        </button>
      </form>
      <p class="mt-4 text-center text-sm text-gray-500">
        No account?
        <a href="/register" class="text-violet-700 hover:underline">Register</a>
      </p>
    </div>
  </div>
</template>
