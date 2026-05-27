export { }

declare global {
  interface Window {
    wplb: {
      baseUrl: string
      root: string
      nonce: string
      editPagesUrl: string
      shortcodes: {
        cookie_policy: string
        privacy_policy: string
        terms_of_service: string
      }
    }
  }
}
