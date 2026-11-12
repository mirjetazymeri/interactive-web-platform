export function navigationState(menuPath: string, currentPath: string) {
  const path = menuPath.replace(/^\/+|\/+$/g, "");
  const current = currentPath.replace(/^\/+|\/+$/g, "");
  if (path === current) return "page" as const;
  if (path && path !== "home" && current.startsWith(`${path}/`)) return "location" as const;
}
