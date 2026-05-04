<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface UserRow {
  id: number
  name: string
  email: string
  plan: string
  role: string | null
  space_count: number
  last_active_at: string | null
  deactivated_at: string | null
}

interface PaginatedUsers {
  data: UserRow[]
  current_page: number
  last_page: number
  next_page_url: string | null
  prev_page_url: string | null
}

interface Filters {
  search?: string
  sort?: string
  dir?: string
}

const { users, filters } = defineProps<{ users: PaginatedUsers, filters: Filters }>()

const search = ref(filters.search ?? '')

function applySearch(): void {
  router.get('/admin/users', { search: search.value }, { preserveState: true, replace: true })
}

function sortUrl(column: string): string {
  const newDir = filters.sort === column && filters.dir === 'asc' ? 'desc' : 'asc'
  return `/admin/users?sort=${column}&dir=${newDir}${search.value ? `&search=${search.value}` : ''}`
}
</script>

<template>
  <div class="min-h-screen bg-[#F7F5FF]">
    <header class="border-b border-gray-200 bg-white px-8 py-4">
      <div class="mx-auto flex max-w-5xl items-center justify-between">
        <h1 class="text-xl font-bold text-[#1A0B3B]">
          Admin — Users
        </h1>
        <Link
          href="/admin/users/invite"
          class="rounded-lg bg-[#FF7043] px-4 py-2 text-sm font-medium text-white hover:bg-[#FF8A65]"
        >
          Invite User
        </Link>
      </div>
    </header>

    <main class="mx-auto max-w-5xl px-8 py-8">
      <form class="mb-4" @submit.prevent="applySearch">
        <input
          v-model="search"
          type="search"
          placeholder="Search by name or email…"
          class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-violet-400"
        >
      </form>

      <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
          <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
            <tr>
              <th class="px-5 py-3">
                <Link :href="sortUrl('name')">
                  Name
                </Link>
              </th>
              <th class="px-5 py-3">
                <Link :href="sortUrl('email')">
                  Email
                </Link>
              </th>
              <th class="px-5 py-3">
                Role
              </th>
              <th class="px-5 py-3">
                Plan
              </th>
              <th class="px-5 py-3">
                <Link :href="sortUrl('space_count')">
                  Spaces
                </Link>
              </th>
              <th class="px-5 py-3">
                <Link :href="sortUrl('last_active_at')">
                  Last Active
                </Link>
              </th>
              <th class="px-5 py-3">
                Status
              </th>
              <th class="px-5 py-3" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="user in users.data"
              :key="user.id"
              :class="{ 'opacity-50': user.deactivated_at }"
            >
              <td class="px-5 py-3 font-medium text-[#1A0B3B]">
                {{ user.name }}
              </td>
              <td class="px-5 py-3 text-gray-500">
                {{ user.email }}
              </td>
              <td class="px-5 py-3">
                <span class="rounded-full bg-[#EDE7FF] px-2 py-0.5 text-xs font-medium text-violet-700">
                  {{ user.role ?? '—' }}
                </span>
              </td>
              <td class="px-5 py-3 text-gray-500 capitalize">
                {{ user.plan }}
              </td>
              <td class="px-5 py-3 text-gray-500">
                {{ user.space_count }}
              </td>
              <td class="px-5 py-3 text-gray-500 text-xs">
                {{ user.last_active_at ?? 'Never' }}
              </td>
              <td class="px-5 py-3">
                <span
                  v-if="user.deactivated_at"
                  class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-600"
                >
                  Deactivated
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <Link
                  :href="`/admin/users/${user.id}`"
                  class="text-violet-700 hover:underline"
                >
                  View
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="users.last_page > 1" class="mt-6 flex justify-center gap-3">
        <Link
          v-if="users.prev_page_url"
          :href="users.prev_page_url"
          class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm hover:bg-gray-50"
        >
          Previous
        </Link>
        <Link
          v-if="users.next_page_url"
          :href="users.next_page_url"
          class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm hover:bg-gray-50"
        >
          Next
        </Link>
      </div>
    </main>
  </div>
</template>
