export {}

declare global {
  interface Window {
    wplb: {
      baseUrl: string
      root: string
      nonce: string
      editPagesUrl: string
    }
  }
}
