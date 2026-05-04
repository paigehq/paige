<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'

interface SpaceData {
  id: number
  name: string
  slug: string
}

interface Member {
  id: number
  name: string
  email: string
  action: string
}

interface GroupMember {
  id: number
  name: string
}

interface Group {
  id: number
  name: string
  action: string | null
  members: GroupMember[]
}

interface AvailableUser {
  id: number
  name: string
  email: string
}

const { space, members, groups, availableUsers } = defineProps<{
  space: SpaceData
  members: Member[]
  groups: Group[]
  availableUsers: AvailableUser[]
}>()

const addMemberForm = useForm({ user_id: '' as string | number, action: 'read' })
const addGroupForm = useForm({ name: '' })

const actions = ['read', 'comment', 'write', 'admin'] as const

function updateMemberAction(memberId: number, action: string): void {
  useForm({ action }).put(`/s/${space.slug}/settings/members/${memberId}`)
}

function removeMember(memberId: number): void {
  useForm({}).delete(`/s/${space.slug}/settings/members/${memberId}`)
}

function updateGroupPermission(groupId: number, action: string): void {
  useForm({ action }).put(`/s/${space.slug}/settings/groups/${groupId}/permission`)
}

function deleteGroup(groupId: number): void {
  useForm({}).delete(`/s/${space.slug}/settings/groups/${groupId}`)
}
</script>

<template>
  <div class="min-h-screen bg-[#F7F5FF]">
    <header class="border-b border-gray-200 bg-white px-8 py-4">
      <div class="mx-auto flex max-w-4xl items-center gap-4">
        <Link
          :href="`/s/${space.slug}`"
          class="text-sm text-gray-500 hover:text-violet-700"
        >
          ← {{ space.name }}
        </Link>
        <h1 class="text-xl font-bold text-[#1A0B3B]">
          Members &amp; Groups
        </h1>
      </div>
    </header>

    <main class="mx-auto max-w-4xl space-y-8 px-8 py-8">
      <!-- Members panel -->
      <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-6 py-4">
          <h2 class="font-semibold text-[#1A0B3B]">
            Members
          </h2>
        </div>

        <table v-if="members.length" class="w-full text-sm">
          <tbody class="divide-y divide-gray-100">
            <tr v-for="member in members" :key="member.id">
              <td class="px-6 py-3 font-medium text-[#1A0B3B]">
                {{ member.name }}
              </td>
              <td class="px-6 py-3 text-gray-500">
                {{ member.email }}
              </td>
              <td class="px-6 py-3">
                <select
                  :value="member.action"
                  class="rounded-lg border border-gray-300 px-2 py-1 text-xs focus:border-violet-500 focus:outline-none"
                  @change="(e) => updateMemberAction(member.id, (e.target as HTMLSelectElement).value)"
                >
                  <option v-for="a in actions" :key="a" :value="a">
                    {{ a }}
                  </option>
                </select>
              </td>
              <td class="px-6 py-3 text-right">
                <button
                  type="button"
                  class="text-xs text-red-500 hover:underline"
                  @click="removeMember(member.id)"
                >
                  Remove
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="px-6 py-4 text-sm text-gray-400">
          No members yet.
        </p>

        <!-- Add member form -->
        <div class="border-t border-gray-100 px-6 py-4">
          <form
            class="flex flex-wrap items-end gap-3"
            @submit.prevent="addMemberForm.post(`/s/${space.slug}/settings/members`)"
          >
            <div class="min-w-0 flex-1">
              <label class="block text-xs font-medium text-gray-600">Add member</label>
              <select
                v-model="addMemberForm.user_id"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none"
              >
                <option value="" disabled>
                  Select user…
                </option>
                <option v-for="u in availableUsers" :key="u.id" :value="u.id">
                  {{ u.name }} ({{ u.email }})
                </option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600">Permission</label>
              <select
                v-model="addMemberForm.action"
                class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none"
              >
                <option v-for="a in actions" :key="a" :value="a">
                  {{ a }}
                </option>
              </select>
            </div>
            <button
              type="submit"
              :disabled="!addMemberForm.user_id || addMemberForm.processing"
              class="rounded-lg bg-[#7C4DFF] px-4 py-2 text-sm font-medium text-white hover:bg-[#9B6FFF] disabled:opacity-50"
            >
              Add
            </button>
          </form>
        </div>
      </section>

      <!-- Groups panel -->
      <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-6 py-4">
          <h2 class="font-semibold text-[#1A0B3B]">
            Groups
          </h2>
        </div>

        <div v-if="groups.length" class="divide-y divide-gray-100">
          <div v-for="group in groups" :key="group.id" class="px-6 py-4">
            <div class="flex items-center justify-between">
              <span class="font-medium text-[#1A0B3B]">{{ group.name }}</span>
              <div class="flex items-center gap-3">
                <select
                  :value="group.action ?? ''"
                  class="rounded-lg border border-gray-300 px-2 py-1 text-xs focus:border-violet-500 focus:outline-none"
                  @change="(e) => updateGroupPermission(group.id, (e.target as HTMLSelectElement).value)"
                >
                  <option value="" disabled>
                    No permission
                  </option>
                  <option v-for="a in actions" :key="a" :value="a">
                    {{ a }}
                  </option>
                </select>
                <button
                  type="button"
                  class="text-xs text-red-500 hover:underline"
                  @click="deleteGroup(group.id)"
                >
                  Delete
                </button>
              </div>
            </div>
            <p class="mt-1 text-xs text-gray-500">
              {{ group.members.length ? group.members.map(m => m.name).join(', ') : 'No members yet' }}
            </p>
          </div>
        </div>
        <p v-else class="px-6 py-4 text-sm text-gray-400">
          No groups yet.
        </p>

        <!-- Create group form -->
        <div class="border-t border-gray-100 px-6 py-4">
          <form
            class="flex gap-3"
            @submit.prevent="addGroupForm.post(`/s/${space.slug}/settings/groups`)"
          >
            <input
              v-model="addGroupForm.name"
              type="text"
              placeholder="New group name"
              class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none"
            >
            <button
              type="submit"
              :disabled="!addGroupForm.name || addGroupForm.processing"
              class="rounded-lg bg-[#7C4DFF] px-4 py-2 text-sm font-medium text-white hover:bg-[#9B6FFF] disabled:opacity-50"
            >
              Create Group
            </button>
          </form>
        </div>
      </section>
    </main>
  </div>
</template>
