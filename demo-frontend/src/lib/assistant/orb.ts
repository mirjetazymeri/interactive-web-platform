/** The orb's visual states and how lively each one is (0 calm … 1 busy). */
export type OrbState = "idle" | "thinking" | "responding" | "error";

export function orbTarget(state: OrbState) {
  switch (state) {
    case "thinking": return { energy: 1, error: 0 };
    case "responding": return { energy: .55, error: 0 };
    case "error": return { energy: .3, error: 1 };
    default: return { energy: .15, error: 0 };
  }
}
