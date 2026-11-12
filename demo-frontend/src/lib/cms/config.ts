export type Config = Record<string, unknown>;
export const str = (c: Config, key: string, fallback = "") => typeof c[key] === "string" ? c[key] as string : fallback;
export const bool = (c: Config, key: string) => c[key] === true || c[key] === 1 || c[key] === "1";
export const num = (c: Config, key: string, fallback: number, min: number, max: number) => {
  const n = Number(c[key] ?? fallback);
  return Number.isFinite(n) ? Math.max(min, Math.min(max, n)) : fallback;
};
export const items = (c: Config, key = "items"): Config[] => Array.isArray(c[key]) ? c[key].filter((x): x is Config => !!x && typeof x === "object" && !Array.isArray(x)) : [];
