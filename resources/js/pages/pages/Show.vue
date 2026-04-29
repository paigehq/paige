<script setup lang="ts">
import type { PageShowProps } from '@/types/wiki'
import AppLayout from '@/layouts/AppLayout.vue'

const { space, page, tree } = defineProps<PageShowProps>()
</script>

<template>
  <AppLayout :space="space" :tree="tree" :current-page-id="page.id">
    <main class="flex-1 overflow-y-auto px-10 py-8">
      <!-- Breadcrumb -->
      <nav class="mb-4 flex items-center gap-1 text-sm text-gray-500">
        <a :href="`/s/${space.slug}`" class="hover:text-violet-700">{{ space.name }}</a>
        <template v-for="(crumb, i) in page.breadcrumb" :key="crumb.id">
          <span class="text-gray-300">/</span>
          <a
            v-if="i < page.breadcrumb.length - 1"
            :href="`/s/${space.slug}/${crumb.slug}`"
            class="hover:text-violet-700"
          >{{ crumb.title }}</a>
          <span v-else class="text-gray-700">{{ crumb.title }}</span>
        </template>
      </nav>

      <!-- Page actions -->
      <div class="mb-6 flex items-start justify-between gap-4">
        <h1 class="text-3xl font-bold text-gray-900">
          {{ page.title }}
        </h1>
        <div class="flex shrink-0 items-center gap-2 pt-1 text-sm">
          <a
            :href="`/s/${space.slug}/${page.slug}/edit`"
            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-gray-600 hover:border-violet-300 hover:text-violet-700"
          >Edit</a>
          <a
            :href="`/s/${space.slug}/${page.slug}/history`"
            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-gray-600 hover:border-violet-300 hover:text-violet-700"
          >History</a>
        </div>
      </div>

      <p v-if="page.lastEditor" class="mb-8 text-sm text-gray-400">
        Last edited by {{ page.lastEditor.name }}
      </p>

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
    </main>
  </AppLayout>
</template>
