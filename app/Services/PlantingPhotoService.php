<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PlantingPhotoService
{
    public function store(UploadedFile $file, int|string $userId, string $folder = 'plantings'): string
    {
        $disk = Storage::disk($this->diskName());
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';
        $path = sprintf('%s/%s/%s.%s', $folder, $userId, Str::uuid(), $extension);
        $contents = $file->get();

        if ($contents === false) {
            throw new RuntimeException('Não foi possível ler a foto do plantio.');
        }

        $contentType = $file->getMimeType() ?: 'image/jpeg';

        // Do not send ACL headers. Buckets with "Bucket owner enforced"
        // reject PutObject when x-amz-acl is present.
        $disk->put($path, $contents, [
            'ContentType' => $contentType,
        ]);

        return $path;
    }

    public function storeAvatar(UploadedFile $file, int|string $userId): string
    {
        return $this->store($file, $userId, 'avatars');
    }

    public function storeShopLogo(UploadedFile $file, int|string $userId): string
    {
        return $this->store($file, $userId, 'shop-logos');
    }

    /**
     * @param  list<mixed>|null  $paths
     * @return list<string>
     */
    public function publicUrls(?array $paths): array
    {
        $urls = [];

        foreach ($paths ?? [] as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            $url = $this->publicUrl($path);
            if ($url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = Storage::disk($this->diskName());

        if ($this->diskName() === 's3') {
            try {
                return $disk->temporaryUrl($path, now()->addDays(7));
            } catch (Throwable) {
                return $disk->url($path);
            }
        }

        return $disk->url($path);
    }

    /**
     * @param  list<mixed>|null  $paths
     */
    public function deleteMany(?array $paths): void
    {
        $keys = [];

        foreach ($paths ?? [] as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                continue;
            }

            $keys[] = $path;
        }

        if ($keys === []) {
            return;
        }

        try {
            Storage::disk($this->diskName())->delete($keys);
        } catch (Throwable) {
            // ignore cleanup failures
        }
    }

    private function diskName(): string
    {
        return (string) config('filesystems.planting_disk', 's3');
    }
}
