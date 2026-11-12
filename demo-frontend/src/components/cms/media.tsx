"use client";

import { useState } from "react";

// No provider requests, thumbnails, cookies or scripts until explicit per-embed permission.
export function ExternalMedia({ src, title, locale, privacyHref }: { src: string; title: string; locale: string; privacyHref?: string }) {
  const [allowed, setAllowed] = useState(false);
  const sq = locale === "sq";
  return <div className="external-media">
    {allowed ? <>
      <iframe src={src} title={title} referrerPolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-presentation" allow="fullscreen; picture-in-picture" allowFullScreen />
      <button className="text-button" onClick={() => setAllowed(false)}>{sq ? "Hiq përmbajtjen e jashtme" : "Unload external content"}</button>
    </> : <div className="media-placeholder">
      <p>{sq ? "Kjo përmbajtje ofrohet nga një shërbim i jashtëm. Ngarkimi ndan adresën tuaj IP dhe mund të përdorë cookies." : "This content is provided by an external service. Loading it shares your IP address and may use cookies."}</p>
      <button className="button" onClick={() => setAllowed(true)}>{`${sq ? "Lejo dhe ngarko" : "Allow and load"} ${title}`}</button>
      {privacyHref && <a href={privacyHref}>{sq ? "Politika e privatësisë" : "Privacy policy"}</a>}
    </div>}
  </div>;
}
