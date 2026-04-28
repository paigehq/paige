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
