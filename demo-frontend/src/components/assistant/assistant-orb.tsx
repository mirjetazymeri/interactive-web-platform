"use client";

import { useEffect, useRef } from "react";
import { orbTarget, type OrbState } from "@/lib/assistant/orb";
import styles from "./assistant.module.css";

export type { OrbState };

// One full-screen triangle; the fragment shader draws a soft, slowly folding sphere.
const VERTEX = "attribute vec2 p;void main(){gl_Position=vec4(p,0.,1.);}";
const FRAGMENT = `
precision mediump float;
uniform vec2 u_res; uniform float u_time; uniform float u_energy; uniform float u_error; uniform float u_pulse;
uniform vec2 u_pointer; uniform vec3 u_a; uniform vec3 u_b; uniform vec3 u_c;
float h(vec3 p){return fract(sin(dot(p,vec3(127.1,311.7,74.7)))*43758.5453);}
float n3(vec3 p){vec3 i=floor(p),f=fract(p);f=f*f*(3.-2.*f);
  return mix(mix(mix(h(i),h(i+vec3(1,0,0)),f.x),mix(h(i+vec3(0,1,0)),h(i+vec3(1,1,0)),f.x),f.y),
             mix(mix(h(i+vec3(0,0,1)),h(i+vec3(1,0,1)),f.x),mix(h(i+vec3(0,1,1)),h(i+vec3(1,1,1)),f.x),f.y),f.z);}
float fbm(vec3 p){float v=0.,a=.5;for(int i=0;i<3;i++){v+=a*n3(p);p=p*2.02+vec3(3.1,1.7,4.3);a*=.5;}return v;}
mat2 rot(float a){float c=cos(a),s=sin(a);return mat2(c,-s,s,c);}
void main(){
  vec2 uv=(gl_FragCoord.xy*2.-u_res)/min(u_res.x,u_res.y);
  float t=u_time;
  float d=length(uv);
  // Soft silhouette: noise sampled on the circle's direction, so there is no seam anywhere.
  vec2 dir=d>.0001?uv/d:vec2(1.,0.);
  float edge=(n3(vec3(dir*1.2,t*.22))-.5)*(.045+.045*u_energy)+(n3(vec3(dir*2.5,t*.37+7.))-.5)*.022*u_energy;
  float R=.68+edge+.014*u_pulse;
  float inside=smoothstep(R+.006,R-.014,d);
  float z=sqrt(max(0.,1.-min(d*d/(R*R),1.)));
  vec3 n=normalize(vec3(uv/R,z));
  // The colour lives in a slowly turning volume; the pointer leans it a little.
  vec3 p=n;
  p.xz*=rot(t*.11+u_pointer.x*.3);
  p.yz*=rot(t*.07-u_pointer.y*.22);
  float flow=.35+.5*u_energy;
  vec3 q=p*1.3+vec3(0.,0.,t*flow*.3);
  float warp=fbm(q+fbm(q*.9+t*.05)*1.35);
  float core=fbm(p*.8-vec3(t*flow*.18)+warp);
  // An inner core and an outer veil, blended by viewing angle: depth without hard shading.
  vec3 inner=mix(u_b,u_a,smoothstep(.3,.72,core));
  vec3 veil=mix(u_a,u_c,smoothstep(.45,.92,warp));
  float fres=pow(1.-z,1.6);
  vec3 col=mix(inner,veil,.22+.42*fres);
  // Thin luminous filaments where the flow folds: the "fluid" inside.
  col+=u_c*pow(smoothstep(.14,0.,abs(warp-.52)),2.)*(.16+.18*u_energy);
  // Light from within, breathing with activity and swelling briefly when an answer arrives.
  col+=u_c*pow(z,4.)*(.1+.18*u_energy+.28*u_pulse);
  // Volume: lit from the upper left, falling into soft shadow.
  vec3 L=normalize(vec3(-.4+u_pointer.x*.2,.55+u_pointer.y*.2,.75));
  col*=.6+.52*max(0.,dot(n,L));
  col+=pow(max(0.,dot(reflect(-L,n),vec3(0.,0.,1.))),22.)*.16;
  col=mix(col,u_c,pow(1.-z,2.6)*.45);
  col=mix(col,vec3(dot(col,vec3(.3,.59,.11))),u_error*.6);
  // A controlled glow just outside the silhouette.
  // Fades to nothing well inside the canvas, so its square edge never shows.
  float g=exp(-pow(max(0.,d-R)*6.2,2.))*(.22+.3*u_energy+.22*u_pulse)*(1.-inside)*smoothstep(.98,.84,d);
  vec3 gc=mix(u_a,u_c,.55);
  gl_FragColor=vec4(col*inside+gc*g,clamp(inside+g,0.,1.));
}`;

/** Resolves a theme custom property (any CSS colour) to 0–1 RGB through a 1px canvas. */
function themeColor(element: HTMLElement, property: string, fallback: [number, number, number]): [number, number, number] {
  const raw = getComputedStyle(element).getPropertyValue(property).trim();
  const context = raw ? document.createElement("canvas").getContext("2d") : null;
  if (!context) return fallback;
  context.fillStyle = raw;
  context.fillRect(0, 0, 1, 1);
  const [r, g, b] = context.getImageData(0, 0, 1, 1).data;
  return [r / 255, g / 255, b / 255];
}
const lighten = (c: [number, number, number], amount: number) => c.map((v) => v + (1 - v) * amount) as [number, number, number];

function compile(gl: WebGLRenderingContext, type: number, source: string) {
  const shader = gl.createShader(type)!;
  gl.shaderSource(shader, source);
  gl.compileShader(shader);
  if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(shader) ?? "shader");
  return shader;
}

/**
 * The large assistant orb. WebGL with a CSS fallback; colours come from the
 * CMS theme. Renders only while visible, at most at 1.5× pixel density, and
 * holds still for reduced motion (state changes then only shift colour).
 */
export function AssistantOrb({ state }: { state: OrbState }) {
  const host = useRef<HTMLSpanElement>(null);
  const stateRef = useRef(state);
  const redraw = useRef<() => void>(() => {});
  const fallbackNode = useRef<HTMLSpanElement | null>(null);

  useEffect(() => {
    stateRef.current = state;
    redraw.current();
    if (fallbackNode.current) fallbackNode.current.dataset.state = state;
  }, [state]);

  useEffect(() => {
    const wrapper = host.current;
    const stage = wrapper?.parentElement;
    if (!wrapper || !stage) return;
    // Each setup owns its canvas (React never renders one), so a remount never inherits a released context.
    const element = document.createElement("canvas");
    element.className = styles.orbCanvas;
    wrapper.appendChild(element);
    const gl = element.getContext("webgl", { alpha: true, premultipliedAlpha: true, antialias: false, powerPreference: "low-power" });
    // Without WebGL the CSS orb stands in, following the same states.
    const showFallback = () => {
      element.remove();
      const span = document.createElement("span");
      span.className = styles.orbFallback;
      span.dataset.state = stateRef.current;
      wrapper.appendChild(span);
      fallbackNode.current = span;
    };
    if (!gl) { showFallback(); return () => { fallbackNode.current?.remove(); fallbackNode.current = null; }; }
    let program: WebGLProgram;
    try {
      program = gl.createProgram()!;
      gl.attachShader(program, compile(gl, gl.VERTEX_SHADER, VERTEX));
      gl.attachShader(program, compile(gl, gl.FRAGMENT_SHADER, FRAGMENT));
      gl.linkProgram(program);
      if (!gl.getProgramParameter(program, gl.LINK_STATUS)) throw new Error("link");
    } catch { showFallback(); return () => { fallbackNode.current?.remove(); fallbackNode.current = null; }; }
    gl.useProgram(program);
    const buffer = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
    const position = gl.getAttribLocation(program, "p");
    gl.enableVertexAttribArray(position);
    gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);
    const u = (name: string) => gl.getUniformLocation(program, name);
    const [uRes, uTime, uEnergy, uError, uPointer, uPulse] = ["u_res", "u_time", "u_energy", "u_error", "u_pointer", "u_pulse"].map(u);
    const accent = themeColor(stage, "--color-accent", [1, .6, .47]);
    const secondary = themeColor(stage, "--color-secondary", [0, .02, .5]);
    // A deep secondary (navy here) would read as dark blotches; lift it toward the accent for a soft blend.
    const mix = (a: number[], b: number[], k: number) => a.map((v, i) => v + (b[i] - v) * k) as [number, number, number];
    gl.uniform3fv(u("u_a"), accent);
    gl.uniform3fv(u("u_b"), lighten(mix(secondary, accent, .14), .08));
    gl.uniform3fv(u("u_c"), lighten(accent, .5));
    gl.clearColor(0, 0, 0, 0);

    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)");
    let frame = 0, last = performance.now(), time = 7.3, energy = orbTarget(state).energy, error = 0, pulse = 0, lastState = state;
    const pointer = [0, 0], aim = [0, 0];

    const resize = () => {
      const size = Math.max(1, Math.min(stage.clientWidth, stage.clientHeight));
      const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
      element.width = Math.round(size * dpr);
      element.height = Math.round(size * dpr);
      gl.viewport(0, 0, element.width, element.height);
    };
    const draw = () => {
      gl.uniform2f(uRes, element.width, element.height);
      gl.uniform1f(uTime, time);
      gl.uniform1f(uEnergy, energy);
      gl.uniform1f(uError, error);
      gl.uniform2f(uPointer, pointer[0], pointer[1]);
      gl.uniform1f(uPulse, pulse);
      gl.clear(gl.COLOR_BUFFER_BIT);
      gl.drawArrays(gl.TRIANGLES, 0, 3);
    };
    const tick = (now: number) => {
      const dt = Math.min(now - last, 64) / 1000;
      last = now;
      const target = orbTarget(stateRef.current);
      // An answer arriving gives one soft swell that fades out.
      if (stateRef.current !== lastState) {
        if (stateRef.current === "responding") pulse = 1;
        lastState = stateRef.current;
      }
      pulse *= Math.exp(-dt * 2.2);
      const ease = 1 - Math.exp(-dt * 3);
      energy += (target.energy - energy) * ease;
      error += (target.error - error) * ease;
      pointer[0] += (aim[0] - pointer[0]) * ease;
      pointer[1] += (aim[1] - pointer[1]) * ease;
      time += dt * (0.55 + 1.6 * energy);
      draw();
      frame = requestAnimationFrame(tick);
    };
    // Nothing to draw while hidden, collapsed (the phone layout folds the orb away) or for reduced motion.
    const visible = () => stage.clientHeight > 8 && stage.clientWidth > 8;
    const running = () => !reduced.matches && document.visibilityState === "visible" && visible();
    const start = () => { if (!frame && running()) { last = performance.now(); frame = requestAnimationFrame(tick); } };
    const stop = () => { cancelAnimationFrame(frame); frame = 0; };
    // Reduced motion: a still orb whose colour follows the state.
    redraw.current = () => {
      if (frame) return;
      const target = orbTarget(stateRef.current);
      energy = target.energy; error = target.error;
      draw();
    };
    const onVisibility = () => { if (running()) start(); else { stop(); redraw.current(); } };
    const onPointer = (event: PointerEvent) => {
      if (event.pointerType !== "mouse") return;
      aim[0] = (event.clientX / window.innerWidth) * 2 - 1;
      aim[1] = 1 - (event.clientY / window.innerHeight) * 2;
    };
    const onLost = (event: Event) => { event.preventDefault(); stop(); showFallback(); };

    const observer = new ResizeObserver(() => {
      resize();
      if (running()) start();
      else { stop(); if (visible()) draw(); }
    });
    observer.observe(stage);
    resize();
    draw();
    start();
    document.addEventListener("visibilitychange", onVisibility);
    reduced.addEventListener("change", onVisibility);
    window.addEventListener("pointermove", onPointer, { passive: true });
    element.addEventListener("webglcontextlost", onLost);
    return () => {
      stop();
      observer.disconnect();
      document.removeEventListener("visibilitychange", onVisibility);
      reduced.removeEventListener("change", onVisibility);
      window.removeEventListener("pointermove", onPointer);
      element.removeEventListener("webglcontextlost", onLost);
      redraw.current = () => {};
      gl.deleteBuffer(buffer);
      gl.deleteProgram(program);
      gl.getExtension("WEBGL_lose_context")?.loseContext();
      element.remove();
      fallbackNode.current?.remove();
      fallbackNode.current = null;
    };
    // The state is read through a ref; the renderer is set up once.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // The canvas (or the CSS fallback) is created inside this wrapper by the effect above.
  return <span ref={host} className={styles.orbHost} />;
}
