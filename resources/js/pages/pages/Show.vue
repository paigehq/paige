<script setup lang="ts">
import type { PageShowProps } from '@/types/wiki'

const { space, page, tree } = defineProps<PageShowProps>()
</script>

<template>
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 shrink-0 border-r border-gray-200 bg-white px-4 py-6">
      <div class="mb-6">
        <a :href="`/s/${space.slug}`" class="text-lg font-semibold text-gray-900 hover:text-violet-700">
          {{ space.name }}
        </a>
      </div>
      <nav>
        <ul class="space-y-1">
          <li v-for="node in tree" :key="node.id">
            <a
              :href="`/s/${space.slug}/${node.slug}`"
              class="block rounded px-3 py-1.5 text-sm"
              :class="[
                node.id === page.id
                  ? 'bg-violet-100 font-medium text-violet-700'
                  : 'text-gray-700 hover:bg-violet-50 hover:text-violet-700',
              ]"
            >
              {{ node.title }}
            </a>
            <ul v-if="node.children.length" class="ml-4 mt-1 space-y-1">
              <li v-for="child in node.children" :key="child.id">
                <a
                  :href="`/s/${space.slug}/${child.slug}`"
                  class="block rounded px-3 py-1.5 text-sm"
                  :class="[
                    child.id === page.id
                      ? 'bg-violet-100 font-medium text-violet-700'
                      : 'text-gray-700 hover:bg-violet-50 hover:text-violet-700',
                  ]"
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

      <h1 class="mb-2 text-3xl font-bold text-gray-900">
        {{ page.title }}
      </h1>

      <p v-if="page.lastEditor" class="mb-8 text-sm text-gray-400">
        Last edited by {{ page.lastEditor.name }}
      </p>

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
    </main>
  </div>
</template>
