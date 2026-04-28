<script setup lang="ts">
import type { SpaceShowProps } from '@/types/wiki'

const { space, page, tree } = defineProps<SpaceShowProps>()
</script>

<template>
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 shrink-0 border-r border-gray-200 bg-white px-4 py-6">
      <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-900">
          {{ space.name }}
        </h1>
        <p v-if="space.description" class="mt-1 text-sm text-gray-500">
          {{ space.description }}
        </p>
      </div>
      <nav>
        <ul class="space-y-1">
          <li v-for="node in tree" :key="node.id">
            <a
              :href="`/s/${space.slug}/${node.slug}`"
              class="block rounded px-3 py-1.5 text-sm text-gray-700 hover:bg-violet-50 hover:text-violet-700"
            >
              {{ node.title }}
            </a>
            <ul v-if="node.children.length" class="ml-4 mt-1 space-y-1">
              <li v-for="child in node.children" :key="child.id">
                <a
                  :href="`/s/${space.slug}/${child.slug}`"
                  class="block rounded px-3 py-1.5 text-sm text-gray-700 hover:bg-violet-50 hover:text-violet-700"
                >
                  {{ child.title }}
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 px-10 py-8">
      <template v-if="page">
        <!-- Breadcrumb -->
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

        <!-- Rendered page HTML -->
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="prose max-w-none" v-html="page.html" />

        <!-- Child pages -->
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
            No pages yet. Start by creating the first page.
          </p>
        </div>
      </template>
    </main>
  </div>
</template>
