<script setup lang="ts">
import type { ApiSearchResult } from '@/types/search'
import { router, usePage } from '@inertiajs/vue3'
import { computed, nextTick, ref, watch } from 'vue'
import { useCommandPalette } from '@/composables/useCommandPalette'

const props = defineProps<{
  currentSpaceSlug?: string | null
}>()

const { isOpen, close } = useCommandPalette()

const page = usePage()
const authUser = computed(() => (page.props.auth as { user: { name: string } | null }).user)

const query = ref('')
const results = ref<ApiSearchResult[]>([])
const isLoading = ref(false)
const isRateLimited = ref(false)
const hasError = ref(false)
const selectedIndex = ref(-1)

const inputRef = ref<HTMLInputElement | null>(null)

interface QuickAction {
  label: string
  hint: string
  url: string
}

const quickActions = computed<QuickAction[]>(() => {
  const actions: QuickAction[] = [
    { label: 'Browse spaces', hint: 'Go to spaces list', url: '/spaces' },
  ]

  if (authUser.value) {
    const newPageUrl = props.currentSpaceSlug
      ? `/s/${props.currentSpaceSlug}/new`
      : '/spaces'

    actions.unshift({ label: 'Create new page', hint: 'Open new page form', url: newPageUrl })
  }

  return actions
})

const showResults = computed(() => query.value.trim().length >= 2)

// Combined navigable items: quick actions always first, then results
const navigableItems = computed(() => {
  const items: Array<{ url: string }> = [...quickActions.value]

  if (showResults.value) {
    items.push(...results.value.map(r => ({ url: r.page_url })))
  }

  return items
})

// Autofocus and reset state when palette opens
watch(isOpen, (open) => {
  if (open) {
    query.value = ''
    results.value = []
    selectedIndex.value = -1
    isLoading.value = false
    isRateLimited.value = false
    hasError.value = false
    nextTick(() => inputRef.value?.focus())
  }
})

// Debounced search — 200ms after last keystroke, min 2 chars
let debounceTimer: ReturnType<typeof setTimeout>

watch(query, (val) => {
  clearTimeout(debounceTimer)
  isRateLimited.value = false
  hasError.value = false
  selectedIndex.value = -1

  if (val.trim().length < 2) {
    results.value = []
    isLoading.value = false
    return
  }

  isLoading.value = true
  debounceTimer = setTimeout(() => doSearch(val.trim()), 200)
})

async function doSearch(q: string): Promise<void> {
  try {
    const response = await fetch(
      `/api/search?q=${encodeURIComponent(q)}&limit=8`,
      { headers: { Accept: 'application/json' } },
    )

    if (response.status === 429) {
      isRateLimited.value = true
      isLoading.value = false
      return
    }

    if (!response.ok) {
      hasError.value = true
      isLoading.value = false
      return
    }

    const data = await response.json()
    results.value = data.results
  }
  catch {
    hasError.value = true
  }
  finally {
    isLoading.value = false
  }
}

function navigateToItem(url: string): void {
  close()
  router.visit(url)
}

function handleKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') {
    close()
    return
  }

  const count = navigableItems.value.length

  if (count === 0) {
    return
  }

  if (event.key === 'ArrowDown') {
    event.preventDefault()
    selectedIndex.value = Math.min(selectedIndex.value + 1, count - 1)
  }
  else if (event.key === 'ArrowUp') {
    event.preventDefault()
    selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
  }
  else if (event.key === 'Enter' && selectedIndex.value >= 0) {
    event.preventDefault()
    navigateToItem(navigableItems.value[selectedIndex.value].url)
  }
}

// selectedIndex position for quick actions vs results rows
function quickActionIndex(i: number): number {
  return i
}

function resultIndex(i: number): number {
  return quickActions.value.length + i
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-start justify-center pt-[15vh]"
        @click.self="close"
        @keydown="handleKeydown"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40" @click="close" />

        <!-- Panel -->
        <div class="relative z-10 w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
          <!-- Search input -->
          <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3">
            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
            </svg>
            <input
              ref="inputRef"
              v-model="query"
              type="search"
              placeholder="Search pages…"
              class="flex-1 bg-transparent text-sm text-[#1A0B3B] placeholder-gray-400 outline-none"
            >
            <span class="shrink-0 rounded border border-gray-200 px-1.5 py-0.5 text-xs text-gray-400">Esc</span>
          </div>

          <!-- Body -->
          <div class="max-h-[60vh] overflow-y-auto py-2">
            <!-- Quick actions (always visible) -->
            <div v-if="quickActions.length > 0" class="px-2 pb-1">
              <p class="px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
                Quick actions
              </p>
              <button
                v-for="(action, i) in quickActions"
                :key="action.url"
                type="button"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm transition-colors"
                :class="selectedIndex === quickActionIndex(i) ? 'bg-[#EDE7FF] text-[#7C4DFF]' : 'text-gray-700 hover:bg-gray-50'"
                @click="navigateToItem(action.url)"
              >
                <span class="font-medium">{{ action.label }}</span>
                <span class="text-xs text-gray-400">{{ action.hint }}</span>
              </button>
            </div>

            <!-- Loading skeleton -->
            <div v-if="isLoading" class="space-y-1 px-2 py-1">
              <div v-for="n in 3" :key="n" class="rounded-lg px-3 py-3">
                <div class="h-3.5 w-2/3 animate-pulse rounded bg-gray-100" />
                <div class="mt-1.5 h-2.5 w-full animate-pulse rounded bg-gray-100" />
              </div>
            </div>

            <!-- Rate limited -->
            <p v-else-if="isRateLimited" class="px-5 py-4 text-sm text-amber-600">
              Too many searches — slow down a moment and try again.
            </p>

            <!-- Error -->
            <p v-else-if="hasError" class="px-5 py-4 text-sm text-red-500">
              Search unavailable right now. Try again shortly.
            </p>

            <!-- Hint: query too short -->
            <p v-else-if="!showResults && query.length > 0" class="px-5 py-4 text-sm text-gray-400">
              Type at least 2 characters to search…
            </p>

            <!-- No query yet — just show quick actions (already rendered above) -->

            <!-- Results -->
            <template v-else-if="showResults && !isLoading">
              <div v-if="results.length > 0" class="px-2 pb-1 pt-2">
                <p class="px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
                  Pages
                </p>
                <button
                  v-for="(result, i) in results"
                  :key="result.id"
                  type="button"
                  class="block w-full rounded-lg px-3 py-2.5 text-left transition-colors"
                  :class="selectedIndex === resultIndex(i) ? 'bg-[#EDE7FF]' : 'hover:bg-gray-50'"
                  @click="navigateToItem(result.page_url)"
                >
                  <p class="text-sm font-medium text-[#1A0B3B]">
                    {{ result.title }}
                  </p>
                  <p class="mt-0.5 truncate text-xs text-gray-400">
                    {{ result.space_name }}
                    <template v-if="result.excerpt">
                      · {{ result.excerpt.replace(/<[^>]+>/g, '') }}
                    </template>
                  </p>
                </button>
              </div>

              <!-- Empty state -->
              <div v-else class="px-5 py-4">
                <p class="text-sm text-gray-500">
                  No pages found for <strong>{{ query }}</strong>.
                </p>
                <a
                  :href="`/search?q=${encodeURIComponent(query)}`"
                  class="mt-1 block text-sm text-[#7C4DFF] hover:underline"
                  @click="close"
                >
                  Search all results →
                </a>
              </div>
            </template>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
