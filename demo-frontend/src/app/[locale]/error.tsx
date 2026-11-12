"use client";
export default function ErrorPage({ reset }: { reset: () => void }) {
  return <main className="status-page"><p className="eyebrow">Content unavailable</p><h1>We couldn’t load this page.</h1><p>The content service is unavailable or returned an unexpected response. Please try again shortly.</p><button className="button" onClick={reset}>Try again</button></main>;
}
