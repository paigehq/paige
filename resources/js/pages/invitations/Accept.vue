<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

const { email, expired, token } = defineProps<{
  email: string
  expired: boolean
  token: string
}>()

const form = useForm({
  name: '',
  password: '',
  password_confirmation: '',
})

function submit(): void {
  form.post(`/invitations/${token}/accept`)
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-[#F7F5FF] px-4">
    <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
      <h1 class="mb-2 text-2xl font-bold text-[#1A0B3B]">
        Welcome to Paige
      </h1>

      <div v-if="expired" class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">
        <p class="font-medium">
          This invitation has expired.
        </p>
        <p class="mt-1">
          Please contact your administrator to request a new invitation.
        </p>
      </div>

      <template v-else>
        <p class="mb-6 text-sm text-gray-500">
          Set your password to activate your account for
          <strong class="text-[#1A0B3B]">{{ email }}</strong>.
        </p>

        <form class="space-y-4" @submit.prevent="submit">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Your Name</label>
            <input
              v-model="form.name"
              type="text"
              autocomplete="name"
              required
              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400"
            >
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
              {{ form.errors.name }}
            </p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
            <input
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              required
              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400"
            >
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
              {{ form.errors.password }}
            </p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Confirm Password</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              required
              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400"
            >
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-lg bg-[#FF7043] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#FF8A65] disabled:opacity-50"
          >
            Activate Account
          </button>
        </form>
      </template>
    </div>
  </div>
</template>
