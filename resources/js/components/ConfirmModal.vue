<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'

interface Props {
  title: string
  message: string
  confirmLabel?: string
  dangerous?: boolean
}

withDefaults(defineProps<Props>(), {
  confirmLabel: 'Confirm',
  dangerous: false,
})

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()

const mounted = ref(false)

onMounted(() => {
  mounted.value = true
  document.addEventListener('keydown', onKey)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKey)
})

function onKey(e: KeyboardEvent): void {
  if (e.key === 'Escape') {
    emit('cancel')
  }
}
</script>

<template>
  <Teleport to="body" :disabled="!mounted">
    <div class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="$emit('cancel')" />
      <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h2 class="text-base font-semibold text-[#1A0B3B]">
          {{ title }}
        </h2>
        <p class="mt-2 text-sm text-gray-500">
          {{ message }}
        </p>
        <div class="mt-6 flex justify-end gap-3">
          <button
            type="button"
            class="rounded-lg border border-gray-200 px-4 py-2 text-sm hover:bg-gray-50"
            @click="$emit('cancel')"
          >
            Cancel
          </button>
          <button
            type="button"
            :class="dangerous
              ? 'rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700'
              : 'rounded-lg bg-[#7C4DFF] px-4 py-2 text-sm font-medium text-white hover:bg-[#9B6FFF]'"
            @click="$emit('confirm')"
          >
            {{ confirmLabel }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
