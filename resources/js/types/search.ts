export interface ApiSearchResult {
  id: number
  title: string
  excerpt: string
  space_name: string
  space_slug: string
  page_url: string
  updated_at: string
}

export interface ApiSearchResponse {
  results: ApiSearchResult[]
  total: number
  query: string
}
