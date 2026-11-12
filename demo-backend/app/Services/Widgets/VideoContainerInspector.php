<?php

namespace App\Services\Widgets;

use RuntimeException;

/**
 * Bounded, seek-based container checks, not a codec decoder. Rejects truncated
 * containers and audio-only files without reading video payloads into memory.
 */
class VideoContainerInspector
{
    private $stream;

    private int $remaining = 100000;

    public function valid(string $path, string $extension): bool
    {
        $this->stream = fopen($path, 'rb');
        if ($this->stream === false) {
            return false;
        }
        try {
            $size = fstat($this->stream)['size'];

            return $extension === 'webm' ? $this->webm($size) : $this->iso($size);
        } catch (RuntimeException) {
            return false;
        } finally {
            fclose($this->stream);
        }
    }

    private function read(int $offset, int $length): string
    {
        if ($length < 1 || $length > 4096 || fseek($this->stream, $offset) !== 0) {
            throw new RuntimeException('Invalid container offset.');
        }
        $bytes = fread($this->stream, $length);
        if ($bytes === false || strlen($bytes) !== $length) {
            throw new RuntimeException('Truncated container.');
        }

        return $bytes;
    }

    /** @return iterable<array{string, int, int}> */
    private function boxes(int $start, int $end): iterable
    {
        while ($start < $end) {
            if (--$this->remaining < 0 || $end - $start < 8) {
                throw new RuntimeException('Invalid box count or length.');
            }
            $header = $this->read($start, 8);
            $size = unpack('N', $header)[1];
            $headerSize = 8;
            if ($size === 1) {
                $parts = unpack('Nhigh/Nlow', $this->read($start + 8, 8));
                // Uploads are bounded well below 4 GiB; reject oversized boxes.
                if ($parts['high'] !== 0) {
                    throw new RuntimeException('Oversized box.');
                }
                $size = $parts['low'];
                $headerSize = 16;
            } elseif ($size === 0) {
                $size = $end - $start;
            }
            if ($size < $headerSize || $size > $end - $start) {
                throw new RuntimeException('Truncated box.');
            }
            yield [substr($header, 4), $start + $headerSize, $start + $size];
            $start += $size;
        }
    }

    private function iso(int $size): bool
    {
        $video = $data = false;
        foreach ($this->boxes(0, $size) as [$type, $start, $end]) {
            if ($type === 'mdat' && $end > $start) {
                $data = true;
            }
            if ($type === 'moov') {
                foreach ($this->boxes($start, $end) as [$child, $cs, $ce]) {
                    if ($child !== 'trak') {
                        continue;
                    }
                    $handler = $samples = false;
                    foreach ($this->boxes($cs, $ce) as [$track, $ts, $te]) {
                        if ($track !== 'mdia') {
                            continue;
                        }
                        foreach ($this->boxes($ts, $te) as [$media, $ms, $me]) {
                            if ($media === 'hdlr' && $me - $ms >= 24 && $this->read($ms + 8, 4) === 'vide') {
                                $handler = true;
                            }
                            if ($media === 'minf') {
                                $samples = $this->videoSampleDescription($ms, $me);
                            }
                        }
                    }
                    $video = $video || ($handler && $samples);
                }
            }
        }

        return $video && $data;
    }

    private function videoSampleDescription(int $start, int $end): bool
    {
        $valid = false;
        foreach ($this->boxes($start, $end) as [$type, $bs, $be]) {
            if ($type !== 'stbl') {
                continue;
            }
            foreach ($this->boxes($bs, $be) as [$table, $ts, $te]) {
                if ($table !== 'stsd' || $te - $ts < 8) {
                    continue;
                }
                $count = unpack('N', $this->read($ts + 4, 4))[1];
                $entries = 0;
                foreach ($this->boxes($ts + 8, $te) as [$codec, $cs, $ce]) {
                    $entries++;
                    if ($ce - $cs >= 78) {
                        $dimensions = unpack('nwidth/nheight', $this->read($cs + 24, 4));
                        $valid = $valid || ($dimensions['width'] > 0 && $dimensions['height'] > 0);
                    }
                }
                if ($entries !== $count) {
                    throw new RuntimeException('Invalid sample description count.');
                }
            }
        }

        return $valid;
    }

    /** @return array{int, int, bool} */
    private function vint(int $offset, bool $id = false): array
    {
        $first = ord($this->read($offset, 1));
        $mask = 128;
        $width = 1;
        while ($width <= 8 && ! ($first & $mask)) {
            $mask >>= 1;
            $width++;
        }
        if ($width > ($id ? 4 : 8)) {
            throw new RuntimeException('Invalid EBML integer.');
        }
        $bytes = $this->read($offset, $width);
        $value = $id ? $first : ($first & ($mask - 1));
        $unknown = ! $id && $value === $mask - 1;
        for ($i = 1; $i < $width; $i++) {
            $byte = ord($bytes[$i]);
            $value = ($value << 8) | $byte;
            $unknown = $unknown && $byte === 255;
        }

        return [$value, $width, $unknown];
    }

    private function elements(int $start, int $end): iterable
    {
        while ($start < $end) {
            if (--$this->remaining < 0) {
                throw new RuntimeException('Too many EBML elements.');
            }
            [$id, $idWidth] = $this->vint($start, true);
            [$size, $sizeWidth, $unknown] = $this->vint($start + $idWidth);
            $body = $start + $idWidth + $sizeWidth;
            if ($unknown && $id !== 0x18538067) {
                throw new RuntimeException('Unfinalized EBML element.');
            }
            $next = $unknown ? $end : $body + $size;
            if ($body > $end || $next > $end || $next < $body) {
                throw new RuntimeException('Truncated EBML element.');
            }
            yield [$id, $body, $next];
            $start = $next;
        }
    }

    private function webm(int $size): bool
    {
        $doctype = $video = $data = false;
        foreach ($this->elements(0, $size) as [$id, $start, $end]) {
            if ($id === 0x1A45DFA3) {
                foreach ($this->elements($start, $end) as [$child, $cs, $ce]) {
                    if ($child === 0x4282 && $ce - $cs === 4 && $this->read($cs, 4) === 'webm') {
                        $doctype = true;
                    }
                }
            }
            if ($id !== 0x18538067) {
                continue;
            }
            foreach ($this->elements($start, $end) as [$child, $cs, $ce]) {
                if ($child === 0x1654AE6B) {
                    foreach ($this->elements($cs, $ce) as [$track, $ts, $te]) {
                        if ($track !== 0xAE) {
                            continue;
                        }
                        $isVideo = $hasCodec = false;
                        foreach ($this->elements($ts, $te) as [$field, $fs, $fe]) {
                            if ($field === 0x83 && $fe - $fs === 1 && ord($this->read($fs, 1)) === 1) {
                                $isVideo = true;
                            }
                            if ($field === 0x86 && $fe - $fs >= 3 && $fe - $fs <= 64) {
                                $hasCodec = str_starts_with($this->read($fs, $fe - $fs), 'V_');
                            }
                        }
                        $video = $video || ($isVideo && $hasCodec);
                    }
                }
                if ($child === 0x1F43B675) {
                    foreach ($this->elements($cs, $ce) as [$block, $bs, $be]) {
                        if ($block === 0xA3 && $be - $bs > 4) {
                            $data = true;
                        }
                        if ($block === 0xA0) {
                            foreach ($this->elements($bs, $be) as [$group, $gs, $ge]) {
                                $data = $data || ($group === 0xA1 && $ge - $gs > 4);
                            }
                        }
                    }
                }
            }
        }

        return $doctype && $video && $data;
    }
}
