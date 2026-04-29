<script setup lang="ts">
import type { SpaceShowProps } from '@/types/wiki'
import AppLayout from '@/layouts/AppLayout.vue'

const { space, page, tree } = defineProps<SpaceShowProps>()
</script>

<template>
  <AppLayout :space="space" :tree="tree" :current-page-id="page?.id ?? null">
    <main class="flex-1 overflow-y-auto px-10 py-8">
      <template v-if="page">
        <nav class="mb-4 flex items-center gap-1 text-sm text-gray-500">
          <template v-for="(crumb, i) in page.breadcrumb" :key="crumb.id">
            <span v-if="i > 0" class="text-gray-300">/</span>
            <a
              v-if="i < page.breadcrumb.length - 1"
              :href="`/s/${space.slug}/${crumb.slug}`"
              class="hover:text-violet-700"
            >{{ crumb.title }}</a>
            <span v-else class="text-gray-700">{{ crumb.title }}</span>
          </template>
        </nav>

        <h1 class="mb-6 text-3xl font-bold text-gray-900">
          {{ page.title }}
        </h1>

        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="prose max-w-none" v-html="page.html" />

        <section v-if="page.children.length" class="mt-10">
          <h2 class="mb-3 text-lg font-semibold text-gray-700">
            Pages in this section
          </h2>
          <ul class="space-y-2">
            <li v-for="child in page.children" :key="child.id">
              <a
                :href="`/s/${space.slug}/${child.slug}`"
                class="font-medium text-violet-700 hover:underline"
              >{{ child.title }}</a>
            </li>
          </ul>
        </section>
      </template>

      <template v-else>
        <div class="flex flex-col items-center justify-center py-24 text-center">
          <h2 class="text-2xl font-semibold text-gray-700">
            Welcome to {{ space.name }}
          </h2>
          <p class="mt-2 text-gray-500">
            No pages yet. Create the first page to get started.
          </p>
          <a
            :href="`/s/${space.slug}/new`"
            class="mt-6 rounded-lg bg-[#FF7043] px-5 py-2 text-sm font-medium text-white hover:bg-[#FF8A65]"
          >New page</a>
        </div>
      </template>
    </main>
  </AppLayout>
</template>
