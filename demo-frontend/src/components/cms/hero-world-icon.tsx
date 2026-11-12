import type { HeroWorldIcon } from "@/lib/cms/hero-world";

// 16px line icons drawn in currentColor, so they always follow the theme.
const paths: Record<HeroWorldIcon, string> = {
  dot: "M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z",
  code: "M5.5 5 2.5 8l3 3M10.5 5l3 3-3 3",
  cloud: "M4.5 12.5h7a3 3 0 0 0 .4-6 4 4 0 0 0-7.6 1.1A2.5 2.5 0 0 0 4.5 12.5Z",
  data: "M3 4c0-1.1 2.2-2 5-2s5 .9 5 2-2.2 2-5 2-5-.9-5-2Zm0 0v8c0 1.1 2.2 2 5 2s5-.9 5-2V4M3 8c0 1.1 2.2 2 5 2s5-.9 5-2",
  ai: "M8 2.5v2M8 11.5v2M2.5 8h2M11.5 8h2M5 5h6v6H5ZM7 7h2v2H7Z",
  project: "M3 4.5h10M3 8h7M3 11.5h4M12 10.5l1 1 2-2.5",
  design: "M10.5 2.5 13.5 5.5 6 13H3v-3ZM9 4l3 3",
  security: "M8 2 3.5 3.8v3.7c0 3 2 5.2 4.5 6.5 2.5-1.3 4.5-3.5 4.5-6.5V3.8ZM6 8l1.5 1.5L10.5 6.5",
  support: "M3 9V8a5 5 0 0 1 10 0v1M3 9h2v3H3.8A.8.8 0 0 1 3 11.2ZM13 9h-2v3h1.2a.8.8 0 0 0 .8-.8ZM11 12.5c0 .8-1.3 1.5-3 1.5",
  mobile: "M5 2h6v12H5ZM7.3 12h1.4",
  analytics: "M3 13h10M4.5 10.5v-2M8 10.5v-6M11.5 10.5v-4",
  globe: "M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2ZM2 8h12M8 2c1.7 1.6 2.5 3.6 2.5 6S9.7 12.4 8 14C6.3 12.4 5.5 10.4 5.5 8S6.3 3.6 8 2Z",
};

export function HeroWorldIconSvg({ icon }: { icon: HeroWorldIcon }) {
  return <svg viewBox="0 0 16 16" width="14" height="14" fill={icon === "dot" ? "currentColor" : "none"} stroke={icon === "dot" ? "none" : "currentColor"} strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false"><path d={paths[icon]} /></svg>;
}
