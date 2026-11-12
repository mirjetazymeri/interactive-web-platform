/* eslint-disable @next/next/no-html-link-for-pages -- Full navigation rechecks the backend default locale. */
export default function NotFound() {
  return <main className="status-page"><p className="eyebrow">404</p><h1>Page not found</h1><p>This page or language is unavailable.</p><a className="button" href="/">Return home</a></main>;
}
