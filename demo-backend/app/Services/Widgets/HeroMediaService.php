<?php

namespace App\Services\Widgets;

use App\Models\Widget;
use App\Models\WidgetTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class HeroMediaService
{
    private array $newPaths = [];

    public function transaction(callable $callback): mixed
    {
        if (DB::transactionLevel() !== 0) {
            throw new \LogicException('Hero media writes must own the outer database transaction.');
        }
        $this->newPaths = [];
        $committed = false;
        try {
            return DB::transaction(function () use ($callback, &$committed) {
                DB::afterCommit(function () use (&$committed) {
                    $committed = true;
                });

                return $callback();
            });
        } catch (Throwable $exception) {
            if (! $committed) {
                foreach ($this->newPaths as $path) {
                    $this->deleteFile($path);
                }
            }
            throw $exception;
        } finally {
            $this->newPaths = [];
        }
    }

    public function prepare(Request $request, array $config, array $existing): array
    {
        $media = $existing['media'] ?? null;
        $image = $existing['bg_image'] ?? null;
        $file = $request->file('config.bg_video_file') ?? $request->file('config.bg_image_file');

        if ($file) {
            $type = $request->hasFile('config.bg_video_file') ? 'video' : 'image';
            $extension = $type === 'video' ? strtolower($file->getClientOriginalExtension()) : $file->extension();
            $path = 'widgets/heroes/'.Str::uuid().'.'.$extension;
            // Register before writing: a failed filesystem write can leave a partial file.
            $this->newPaths[] = $path;
            $stored = $file->storeAs('widgets/heroes', basename($path), 'public');
            if ($stored === false) {
                throw new RuntimeException('Unable to store Hero media.');
            }
            $media = [
                'type' => $type,
                'disk' => 'public',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
            $image = $type === 'image' ? $path : null;
        } elseif ($request->boolean('config.remove_media') || $request->boolean('config.remove_bg_image')) {
            $media = $image = null;
        }

        $config['media'] = $media;
        if ($image !== null) {
            $config['bg_image'] = $image;
        } else {
            unset($config['bg_image']);
        }

        return $config;
    }

    public function retire(array $old, array $new = [], ?int $deletedWidgetId = null): void
    {
        $oldPath = $old['media']['path'] ?? $old['bg_image'] ?? null;
        $newPath = $new['media']['path'] ?? $new['bg_image'] ?? null;
        if (! is_string($oldPath) || $oldPath === $newPath || ! $this->ownedPath($oldPath)) {
            return;
        }

        DB::afterCommit(function () use ($oldPath, $deletedWidgetId) {
            try {
                // Include soft-deleted records: their files may be intentionally shared.
                foreach (Widget::withTrashed()->when($deletedWidgetId, fn ($q) => $q->where('id', '!=', $deletedWidgetId))->cursor() as $widget) {
                    if ($this->references($widget->config ?? [], $oldPath)) {
                        return;
                    }
                }
                foreach (WidgetTranslation::query()->cursor() as $translation) {
                    if ($this->references($translation->config ?? [], $oldPath)) {
                        return;
                    }
                }
                $this->deleteFile($oldPath);
            } catch (Throwable $exception) {
                Log::warning('Hero media cleanup failed.', ['disk' => 'public', 'path' => $oldPath, 'exception' => $exception]);
            }
        });
    }

    private function ownedPath(string $path): bool
    {
        // Only our UUID uploads and the previous Laravel hash-named uploads.
        // Never infer ownership from arbitrary legacy URLs or seeded/demo assets.
        return (bool) preg_match('~\Awidgets/(?:heroes/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|[a-zA-Z0-9]{40})\.(?:jpg|jpeg|png|gif|webp|bmp|avif|mp4|webm|mov)\z~D', $path);
    }

    private function references(array $config, string $path): bool
    {
        foreach ($config as $value) {
            if (is_array($value) && $this->references($value, $path)) {
                return true;
            }
            if (is_string($value) && $this->normalizePath($value) === $path) {
                return true;
            }
        }

        return false;
    }

    private function deleteFile(string $path): void
    {
        try {
            if (! Storage::disk('public')->delete($path)) {
                throw new RuntimeException('Storage returned false while deleting media.');
            }
        } catch (Throwable $exception) {
            Log::warning('Hero media cleanup failed.', ['disk' => 'public', 'path' => $path, 'exception' => $exception]);
        }
    }

    public function representation(array $config): ?array
    {
        $media = $config['media'] ?? null;
        if (is_array($media) && ! empty($media['path'])) {
            return [
                'type' => $media['type'],
                'url' => Storage::disk('public')->url($media['path']),
                'mime_type' => $media['mime_type'] ?? null,
                'size' => $media['size'] ?? null,
            ];
        }
        // Legacy records remain images without a data rewrite or storage I/O.
        if (! empty($config['bg_image'])) {
            return [
                'type' => 'image',
                'url' => Storage::disk('public')->url($this->normalizePath($config['bg_image'])),
                'mime_type' => null,
                'size' => null,
            ];
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }
        $path = ltrim($path, '/');

        return Str::startsWith($path, 'storage/') ? substr($path, 8) : $path;
    }
}
