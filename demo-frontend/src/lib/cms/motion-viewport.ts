/** A small-viewport probe keeps mobile toolbar expansion from changing a pin's
 * runway on every scroll. Desktop svh follows the normal viewport. */
export function motionViewport(root: HTMLElement, invalidate: () => void) {
  const probe = typeof document.createElement === "function" ? document.createElement("span") : null;
  if (probe) {
    probe.setAttribute("aria-hidden", "true");
    probe.style.cssText = "position:fixed;top:0;left:0;width:0;height:100svh;visibility:hidden;pointer-events:none;contain:strict";
    root.appendChild(probe);
  }
  const viewport = window.visualViewport;
  viewport?.addEventListener("resize", invalidate, { passive: true });
  document.fonts?.addEventListener("loadingdone", invalidate);
  root.addEventListener("load", invalidate, { capture: true, passive: true });
  return {
    height: () => probe?.getBoundingClientRect().height || window.innerHeight,
    dispose() {
      viewport?.removeEventListener("resize", invalidate);
      document.fonts?.removeEventListener("loadingdone", invalidate);
      root.removeEventListener("load", invalidate, { capture: true });
      probe?.remove();
    },
  };
}
