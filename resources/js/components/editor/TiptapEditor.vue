<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { Extension } from '@tiptap/core'
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import Underline from '@tiptap/extension-underline'
import StarterKit from '@tiptap/starter-kit'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import { useDebounceFn, useEventListener } from '@vueuse/core'
import { Plugin } from 'prosemirror-state'
import { computed, onBeforeUnmount, ref } from 'vue'

const props = defineProps<{
  initialTitle: string
  initialContent: string | null
  saveUrl: string
}>()

const emit = defineEmits<{
  published: []
}>()

const title = ref(props.initialTitle)
const isDirty = ref(false)
const saveStatus = ref<'idle' | 'saving' | 'saved' | 'error'>('idle')
const showM2Toast = ref(false)

const BlockImageDrop = Extension.create({
  name: 'blockImageDrop',
  addProseMirrorPlugins() {
    return [
      new Plugin({
        props: {
          handleDrop(_view, event) {
            const files = Array.from(event.dataTransfer?.files ?? [])
            if (files.some(f => f.type.startsWith('image/'))) {
              event.preventDefault()
              showM2Toast.value = true
              setTimeout(() => {
                showM2Toast.value = false
              }, 3000)
              return true
            }
            return false
          },
        },
      }),
    ]
  },
})

const debouncedSave = useDebounceFn(() => save('draft'), 2000)

const editor = useEditor({
  content: props.initialContent ? JSON.parse(props.initialContent) : null,
  extensions: [
    StarterKit,
    Underline,
    Link.configure({ openOnClick: false, autolink: true }),
    Image,
    BlockImageDrop,
  ],
  onUpdate() {
    isDirty.value = true
    debouncedSave()
  },
})

function save(action: 'draft' | 'publish') {
  if (!editor.value)
    return
  saveStatus.value = 'saving'
  router.put(props.saveUrl, {
    title: title.value,
    content: JSON.stringify(editor.value.getJSON()),
    action,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess() {
      isDirty.value = false
      saveStatus.value = 'saved'
      if (action === 'publish')
        emit('published')
    },
    onError() {
      saveStatus.value = 'error'
    },
  })
}

const autosaveInterval = setInterval(() => {
  if (isDirty.value)
    save('draft')
}, 30_000)

useEventListener(document, 'keydown', (e: KeyboardEvent) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault()
    save(e.shiftKey ? 'draft' : 'publish')
  }
})

useEventListener(window, 'beforeunload', (e: BeforeUnloadEvent) => {
  if (isDirty.value) {
    e.preventDefault()
    e.returnValue = ''
  }
})

onBeforeUnmount(() => {
  clearInterval(autosaveInterval)
  editor.value?.destroy()
})

const toolbarButtons = computed(() => {
  const e = editor.value
  if (!e)
    return []
  return [
    { label: 'B', action: () => e.chain().focus().toggleBold().run(), active: () => e.isActive('bold') },
    { label: 'I', action: () => e.chain().focus().toggleItalic().run(), active: () => e.isActive('italic') },
    { label: 'U', action: () => e.chain().focus().toggleUnderline().run(), active: () => e.isActive('underline') },
    { label: 'S̶', action: () => e.chain().focus().toggleStrike().run(), active: () => e.isActive('strike') },
    { label: '`', action: () => e.chain().focus().toggleCode().run(), active: () => e.isActive('code') },
    { label: 'H1', action: () => e.chain().focus().toggleHeading({ level: 1 }).run(), active: () => e.isActive('heading', { level: 1 }) },
    { label: 'H2', action: () => e.chain().focus().toggleHeading({ level: 2 }).run(), active: () => e.isActive('heading', { level: 2 }) },
    { label: 'H3', action: () => e.chain().focus().toggleHeading({ level: 3 }).run(), active: () => e.isActive('heading', { level: 3 }) },
    { label: '• List', action: () => e.chain().focus().toggleBulletList().run(), active: () => e.isActive('bulletList') },
    { label: '1. List', action: () => e.chain().focus().toggleOrderedList().run(), active: () => e.isActive('orderedList') },
    { label: '"', action: () => e.chain().focus().toggleBlockquote().run(), active: () => e.isActive('blockquote') },
    { label: '</>', action: () => e.chain().focus().toggleCodeBlock().run(), active: () => e.isActive('codeBlock') },
    { label: '—', action: () => e.chain().focus().setHorizontalRule().run(), active: () => false },
  ]
})
</script>

<template>
  <div class="flex flex-col">
    <!-- Status + action bar -->
    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2 text-sm">
      <span>
        <span v-if="saveStatus === 'saving'" class="text-gray-500">Saving…</span>
        <span v-else-if="saveStatus === 'saved'" class="text-green-600">Saved</span>
        <span v-else-if="saveStatus === 'error'" class="text-red-600">Save failed</span>
        <span v-else-if="isDirty" class="text-amber-600">Unsaved changes</span>
      </span>
      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-lg px-3 py-1 text-sm text-gray-600 hover:bg-gray-200"
          @click="save('draft')"
        >
          Save draft
          <span class="ml-1 text-xs text-gray-400">Ctrl+⇧+S</span>
        </button>
        <button
          type="button"
          class="rounded-lg bg-[#FF7043] px-3 py-1 text-sm font-medium text-white hover:bg-[#FF8A65]"
          @click="save('publish')"
        >
          Publish
          <span class="ml-1 text-xs opacity-70">Ctrl+S</span>
        </button>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-white px-3 py-2">
      <template v-for="(btn, i) in toolbarButtons" :key="i">
        <button
          type="button"
          class="rounded px-2 py-1 text-sm hover:bg-violet-50"
          :class="{ 'bg-violet-100 text-violet-700': btn.active() }"
          @click="btn.action()"
        >
          {{ btn.label }}
        </button>
      </template>
      <div class="mx-1 h-5 w-px bg-gray-200" />
      <button
        type="button"
        class="cursor-not-allowed rounded px-2 py-1 text-sm text-gray-400"
        title="Image upload coming in Milestone 2"
        disabled
      >
        IMG
      </button>
    </div>

    <!-- Title input -->
    <input
      v-model="title"
      type="text"
      placeholder="Page title"
      class="border-b border-gray-100 px-6 py-4 text-3xl font-bold text-gray-900 outline-none placeholder:text-gray-300"
      @input="isDirty = true"
    >

    <!-- Editor body -->
    <EditorContent
      v-if="editor"
      :editor="editor"
      class="prose max-w-none flex-1 px-6 py-6 focus:outline-none"
    />

    <!-- Milestone 2 image drop toast -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-2"
    >
      <div
        v-if="showM2Toast"
        class="fixed bottom-6 right-6 rounded-lg bg-gray-800 px-4 py-2 text-sm text-white shadow-lg"
      >
        Image upload is coming in Milestone 2
      </div>
    </Transition>
  </div>
</template>
