export interface SpaceData {
  id: number
  name: string
  slug: string
  description: string | null
}

export interface BreadcrumbItem {
  id: number
  title: string
  slug: string
}

export interface ChildPageItem {
  id: number
  title: string
  slug: string
  position: number
}

export interface TreeNode {
  id: number
  title: string
  slug: string
  position: number
  isDraft: boolean
  children: TreeNode[]
}

export interface PageViewData {
  id: number
  title: string
  slug: string
  html: string
  breadcrumb: BreadcrumbItem[]
  children: ChildPageItem[]
  lastEditor: { id: number, name: string } | null
  updatedAt: string
}

export interface SpaceShowProps {
  space: SpaceData
  page: PageViewData | null
  tree: TreeNode[]
}

export interface PageShowProps {
  space: SpaceData
  page: PageViewData
  tree: TreeNode[]
}

export interface PageEditData {
  id: number
  title: string
  slug: string
  content: string | null
  status: 'draft' | 'published'
  revisionNumber: number
}

export interface RevisionSummary {
  number: number
  editorName: string
  changeSummary: string | null
  createdAt: string
}

export interface RevisionDetail {
  number: number
  title: string
  html: string
  editorName: string
  createdAt: string
}

export interface DiffLine {
  tag: 'equal' | 'insert' | 'delete'
  line: string
}

export interface DiffRevisionMeta {
  number: number
  editorName: string
  createdAt: string
}

export interface PageCreateProps {
  space: SpaceData
  tree: TreeNode[]
}

export interface PageEditProps {
  space: SpaceData
  page: PageEditData
  tree: TreeNode[]
}

export interface PageHistoryProps {
  space: SpaceData
  page: { id: number, title: string, slug: string, status: string }
  tree: TreeNode[]
  revisions: RevisionSummary[]
}

export interface RevisionDetailProps {
  space: SpaceData
  page: { id: number, title: string, slug: string, status: string }
  tree: TreeNode[]
  revision: RevisionDetail
}

export interface DiffViewProps {
  space: SpaceData
  page: { id: number, title: string, slug: string, status: string }
  tree: TreeNode[]
  revisionA: DiffRevisionMeta
  revisionB: DiffRevisionMeta
  diff: DiffLine[]
}
