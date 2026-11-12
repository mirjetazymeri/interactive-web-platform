"use client";
export default function GlobalError({ reset }: { reset: () => void }) {
  return <html lang="en"><body><main style={{ padding: "4rem", fontFamily: "sans-serif" }}><h1>Content unavailable</h1><p>The content service or site configuration is unavailable. Please try again shortly.</p><button onClick={reset}>Try again</button></main></body></html>;
}
