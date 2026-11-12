import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Self-contained server build, deployed to whm01 (cPanel Application Manager).
  output: "standalone",
  // Resolve CMS availability before headers are sent, including real HTTP 404s.
  htmlLimitedBots: /.*/,
  experimental: { globalNotFound: true },
};

export default nextConfig;
