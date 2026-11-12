"use client";

import { useEffect, useId, useRef, useState } from "react";
import { heroPlayback, type PlaybackState } from "@/lib/cms/hero-playback";
import { heroColorAccents } from "@/lib/cms/hero-color";

export function HeroVideo({ url, locale }: { url: string; locale: string }) {
  const video = useRef<HTMLVideoElement>(null);
  const playback = useRef<ReturnType<typeof heroPlayback> | null>(null);
  const [state, setState] = useState<PlaybackState>("idle");
  const id = useId();
  const sq = locale === "sq";
  useEffect(() => {
    if (!video.current) return;
    const controller = heroPlayback(video.current, url, setState);
    playback.current = controller;
    return () => { playback.current = null; controller.dispose(); };
  }, [url]);
  useEffect(() => {
    if (!video.current) return;
    return heroColorAccents(video.current);
  }, [url]);
  const active = state === "playing" || state === "loading";
  const message = state === "error"
    ? (sq ? "Videoja e sfondit nuk mund të luhet në këtë shfletues ose nuk është e disponueshme." : "The background video is unavailable or cannot be played in this browser.")
    : state === "blocked"
      ? (sq ? "Luajtja automatike u bllokua. Sfondi mbetet statik." : "Automatic playback was blocked. The background remains still.")
      : state === "loading" ? (sq ? "Videoja po ngarkohet…" : "Loading video…") : "";
  return <>
    <video ref={video} id={id} className="hero-video" muted loop playsInline preload="none" aria-hidden="true" tabIndex={-1} />
    <div className="hero-video-controls">
      {state !== "error" && <button className="hero-motion-toggle" type="button" aria-controls={id} onClick={() => playback.current?.toggle()}>
        {active ? (sq ? "Ndalo videon e sfondit" : "Pause background video") : (sq ? "Luaj videon e sfondit" : "Play background video")}
      </button>}
      <span role="status">{message}</span>
    </div>
  </>;
}
