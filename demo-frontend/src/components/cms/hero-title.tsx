"use client";

import { useEffect, useRef, type CSSProperties } from "react";
import type { heroTitleParts } from "@/lib/cms/hero-title";
import { heroTitleInteraction } from "@/lib/cms/hero-title-interaction";

export function HeroTitle({ title, parts, primary }: { title: string; parts: ReturnType<typeof heroTitleParts>; primary?: boolean }) {
  const Tag = primary ? "h1" : "h2";
  const heading = useRef<HTMLHeadingElement>(null);
  useEffect(() => {
    if (!heading.current || parts.connected) return;
    return heroTitleInteraction(heading.current);
  }, [title, parts.connected, primary]);
  let index = 0;
  return <Tag className="hero-title" ref={heading}>
    <span className="sr-only">{title}</span>
    <span className={`hero-title-visual${parts.connected ? " hero-title-connected" : ""}`} aria-hidden="true">{parts.connected ? title : parts.words.map((word, i) =>
      /^\s+$/u.test(word.join("")) ? <span key={i}>{word.join("")}</span> : <span className="hero-title-word" key={i}>{word.map((letter) => {
        const order = index++;
        return <span className="hero-letter" key={order} style={{ "--letter-delay": `${order * parts.step}ms` } as CSSProperties}><span className="hero-letter-motion">{letter}</span></span>;
      })}</span>
    )}</span>
  </Tag>;
}
