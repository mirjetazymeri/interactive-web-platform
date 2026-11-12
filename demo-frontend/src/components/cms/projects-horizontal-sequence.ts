const clamp = (value: number, max: number) => Math.max(0, Math.min(max, value));

export type ProjectSequence = { travel: number; positions: number[]; readings: number[]; starts: number[]; length: number };

/** Extra reading distance only when panel text exceeds the visible stage. */
export function projectSequence(travel: number, positions: number[], readings: number[]): ProjectSequence {
  let extra = 0;
  const starts = positions.map((position, index) => {
    const start = Math.min(travel, position) + extra;
    extra += readings[index];
    return start;
  });
  return { travel, positions, readings, starts, length: travel + extra };
}

export function projectSequenceFrame(distance: number, sequence: ProjectSequence) {
  let read = 0;
  const progress = sequence.readings.map((length, index) => {
    const used = clamp(distance - sequence.starts[index], length);
    read += used;
    return length > 0 ? used / length : 1;
  });
  return { x: clamp(distance - read, sequence.travel), progress };
}

export function projectSequenceRemap(distance: number, previous: ProjectSequence, next: ProjectSequence) {
  const reading = previous.readings.findIndex((length, index) => length > 0 && distance >= previous.starts[index] && distance <= previous.starts[index] + length);
  if (reading >= 0 && reading < next.starts.length) return next.starts[reading] + (distance - previous.starts[reading]) / previous.readings[reading] * next.readings[reading];
  const x = previous.travel > 0 ? projectSequenceFrame(distance, previous).x / previous.travel * next.travel : 0;
  return x + next.readings.reduce((sum, length, index) => sum + (next.positions[index] < x - .5 ? length : 0), 0);
}
