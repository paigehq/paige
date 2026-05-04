<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  modelValue: string[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
}>()

interface TagSuggestion {
  id: number
  name: string
  slug: string
}

const inputValue = ref('')
const suggestions = ref<TagSuggestion[]>([])
let debounceTimer: ReturnType<typeof setTimeout>

async function onInput(): Promise<void> {
  const q = inputValue.value.trim()

  if (q.length === 0) {
    suggestions.value = []
    return
  }

  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(async () => {
    try {
      const res = await fetch(`/api/tags?q=${encodeURIComponent(q)}`)
      suggestions.value = res.ok ? (await res.json() as TagSuggestion[]) : []
    }
    catch {
      suggestions.value = []
    }
  }, 300)
}

function addTag(name: string): void {
  const trimmed = name.trim()

  if (trimmed !== '' && !props.modelValue.includes(trimmed)) {
    emit('update:modelValue', [...props.modelValue, trimmed])
  }

  inputValue.value = ''
  suggestions.value = []
}

function removeTag(name: string): void {
  emit('update:modelValue', props.modelValue.filter(t => t !== name))
}

function onKeydown(e: KeyboardEvent): void {
  if (e.key === 'Enter' && inputValue.value.trim() !== '') {
    e.preventDefault()
    addTag(inputValue.value)
    return
  }

  if (e.key === 'Backspace' && inputValue.value === '' && props.modelValue.length > 0) {
    removeTag(props.modelValue[props.modelValue.length - 1])
  }
}
</script>

<template>
  <div class="relative">
    <label class="mb-1 block text-sm font-medium text-gray-600">Tags</label>
    <div
      class="flex min-h-10 flex-wrap items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2 py-1.5 focus-within:border-violet-400 focus-within:ring-1 focus-within:ring-violet-200"
    >
      <span
        v-for="tag in modelValue"
        :key="tag"
        class="flex items-center gap-1 rounded-full bg-[#EDE7FF] px-2.5 py-0.5 text-xs font-medium text-violet-800"
      >
        {{ tag }}
        <button
          type="button"
          class="text-violet-400 hover:text-violet-700"
          @click="removeTag(tag)"
        >×</button>
      </span>
      <input
        v-model="inputValue"
        type="text"
        placeholder="Add tags…"
        class="min-w-32 flex-1 bg-transparent text-sm outline-none placeholder:text-gray-400"
        @input="onInput"
        @keydown="onKeydown"
      >
    </div>

    <ul
      v-if="suggestions.length > 0"
      class="absolute z-10 mt-1 w-full rounded-lg border border-gray-100 bg-white py-1 shadow-lg"
    >
      <li
        v-for="s in suggestions"
        :key="s.id"
        class="cursor-pointer px-3 py-1.5 text-sm text-gray-700 hover:bg-[#EDE7FF]"
        @mousedown.prevent="addTag(s.name)"
      >
        {{ s.name }}
      </li>
    </ul>
  </div>
</template>
