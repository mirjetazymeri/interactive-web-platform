import { z } from "zod";
import { safeUrl } from "./links";

// Public WidgetRenderer contract; storage paths and upload fields stay in Laravel.
export const heroMediaSchema = z.object({
  type: z.enum(["image", "video"]),
  url: z.string().refine((value) => !!safeUrl(value, true)),
  mime_type: z.string().nullable(),
  size: z.number().int().nonnegative().nullable(),
});
export type HeroMedia = z.infer<typeof heroMediaSchema> | null;

export function normalizeHeroMedia(config: Record<string, unknown>): HeroMedia {
  // Presence is authoritative: never resurrect a stale bg_image after removal,
  // or when a new media payload is unusable.
  if (Object.hasOwn(config, "media")) {
    const parsed = heroMediaSchema.safeParse(config.media);
    return parsed.success ? parsed.data : null;
  }
  const url = safeUrl(config.bg_image, true);
  return url ? { type: "image", url, mime_type: null, size: null } : null;
}
