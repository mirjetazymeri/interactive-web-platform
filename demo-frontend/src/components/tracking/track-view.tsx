"use client";

import { useEffect } from "react";
import { track, type EventParams, type TrackingEvent } from "@/lib/tracking/events";

/** Records one event when the page is shown (e.g. a project view). Renders nothing. */
export function TrackView({ event, params }: { event: TrackingEvent; params?: EventParams }) {
  const key = JSON.stringify(params ?? {});
  useEffect(() => { track(event, JSON.parse(key)); }, [event, key]);
  return null;
}
