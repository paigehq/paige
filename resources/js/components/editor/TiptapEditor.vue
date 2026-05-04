<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import Underline from '@tiptap/extension-underline'
import StarterKit from '@tiptap/starter-kit'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import { useDebounceFn, useEventListener } from '@vueuse/core'
import { Plugin, PluginKey } from 'prosemirror-state'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps<{
  initialTitle: string
  initialContent: string | null
  saveUrl: string
  spaceSlug: string
  pageSlug: string
}>()

const emit = defineEmits<{
  published: []
}>()

const title = ref(props.initialTitle)
const isDirty = ref(false)
const saveStatus = ref<'idle' | 'saving' | 'saved' | 'error'>('idle')
const uploadError = ref(false)
const fileInputRef = ref<HTMLInputElement | null>(null)

// Read the XSRF-TOKEN cookie — required for web-route POSTs since Axios was removed in Inertia v3
function getCsrfToken(): string {
  return decodeURIComponent(
    document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1] ?? '',
  )
}

function showUploadError(): void {
  uploadError.value = true
  setTimeout(() => {
    uploadError.value = false
  }, 3000)
}

const imageUploadPluginKey = new PluginKey('imageUpload')

const ImageUploadPlugin = new Plugin({
  key: imageUploadPluginKey,
  props: {
    handleDrop(_view, event) {
      const files = Array.from(event.dataTransfer?.files ?? [])
        .filter(f => f.type.startsWith('image/'))

      if (files.length === 0) {
        return false
      }

      event.preventDefault()

      const coords = { left: event.clientX, top: event.clientY }
      const pos = _view.posAtCoords(coords)?.pos ?? _view.state.doc.content.size

      files.forEach(file => uploadFile(file, pos))
      return true
    },

    handlePaste(_view, event) {
      const files = Array.from(event.clipboardData?.files ?? [])
        .filter(f => f.type.startsWith('image/'))

      if (files.length === 0) {
        return false
      }

      event.preventDefault()

      const pos = _view.state.selection.from

      files.forEach(file => uploadFile(file, pos))
      return true
    },
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
  ],
  editorProps: {
    handleDrop: ImageUploadPlugin.props.handleDrop,
    handlePaste: ImageUploadPlugin.props.handlePaste,
  },
  onUpdate() {
    isDirty.value = true
    debouncedSave()
  },
})

function uploadFile(file: File, insertPos: number): void {
  if (!editor.value) {
    return
  }

  const view = editor.value.view
  const objectUrl = URL.createObjectURL(file)

  // Insert a placeholder image node immediately with the blob URL as src
  const imageNode = view.state.schema.nodes.image.create({ src: objectUrl })
  const tr = view.state.tr.insert(insertPos, imageNode)
  view.dispatch(tr)

  const formData = new FormData()
  formData.append('file', file)

  fetch(`/s/${props.spaceSlug}/${props.pageSlug}/attachments`, {
    method: 'POST',
    body: formData,
    headers: {
      'X-XSRF-TOKEN': getCsrfToken(),
      'Accept': 'application/json',
    },
  })
    .then((res) => {
      if (!res.ok) {
        throw new Error(`Upload failed: ${res.status}`)
      }
      return res.json()
    })
    .then((data: { url: string }) => {
      // Swap the blob URL for the signed URL in-place
      const { state } = view
      let nodePos: number | null = null

      state.doc.descendants((node, pos) => {
        if (node.type.name === 'image' && node.attrs.src === objectUrl) {
          nodePos = pos
          return false
        }
      })

      if (nodePos !== null) {
        const swap = state.tr.setNodeMarkup(nodePos, null, { ...view.state.doc.nodeAt(nodePos)?.attrs, src: data.url })
        view.dispatch(swap)
      }

      URL.revokeObjectURL(objectUrl)
      isDirty.value = true
    })
    .catch(() => {
      // Remove the placeholder node
      const { state } = view
      let nodePos: number | null = null
      let nodeSize = 0

      state.doc.descendants((node, pos) => {
        if (node.type.name === 'image' && node.attrs.src === objectUrl) {
          nodePos = pos
          nodeSize = node.nodeSize
          return false
        }
      })

      if (nodePos !== null) {
        const remove = state.tr.delete(nodePos, nodePos + nodeSize)
        view.dispatch(remove)
      }

      URL.revokeObjectURL(objectUrl)
      showUploadError()
    })
}

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

function triggerImagePicker(): void {
  fileInputRef.value?.click()
}

function onImageFilePicked(event: Event): void {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? []).filter(f => f.type.startsWith('image/'))

  if (!editor.value || files.length === 0) {
    return
  }

  const pos = editor.value.view.state.selection.from
  files.forEach(file => uploadFile(file, pos))

  // Reset input so the same file can be re-selected
  input.value = ''
}

let autosaveInterval: ReturnType<typeof setInterval> | undefined

onMounted(() => {
  autosaveInterval = setInterval(() => {
    if (isDirty.value)
      save('draft')
  }, 30_000)
})

useEventListener(
  typeof document !== 'undefined' ? document : null,
  'keydown',
  (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault()
      save(e.shiftKey ? 'draft' : 'publish')
    }
  },
)

useEventListener(
  typeof window !== 'undefined' ? window : null,
  'beforeunload',
  (e: BeforeUnloadEvent) => {
    if (isDirty.value) {
      e.preventDefault()
      e.returnValue = ''
    }
  },
)

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
    <!-- Hidden file input for the IMG toolbar button -->
    <input
      ref="fileInputRef"
      type="file"
      accept="image/*"
      class="hidden"
      multiple
      @change="onImageFilePicked"
    >

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
        class="rounded px-2 py-1 text-sm hover:bg-violet-50"
        title="Insert image"
        @click="triggerImagePicker"
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

    <!-- Upload error toast -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-2"
    >
      <div
        v-if="uploadError"
        class="fixed bottom-6 right-6 rounded-lg bg-red-600 px-4 py-2 text-sm text-white shadow-lg"
      >
        Image upload failed. Please try again.
      </div>
    </Transition>
  </div>
</template>
