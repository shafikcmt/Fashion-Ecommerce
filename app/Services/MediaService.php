<?php

namespace App\Services;

use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Setting;
use App\Models\Variant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class MediaService
{
    public function upload(UploadedFile $file, string $alt): Media
    {
        Validator::make(['file' => $file, 'alt' => $alt], ['file' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'], 'alt' => ['required', 'string', 'max:255']])->validate();
        $disk = config('commerce.media_disk', 'public');
        $path = $file->store('media', $disk);
        try {
            return Media::create(['disk' => $disk, 'path' => $path, 'alt' => $alt, 'mime' => $file->getMimeType(), 'bytes' => $file->getSize()]);
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);
            throw $e;
        }
    }

    public function delete(Media $media): void
    {
        DB::transaction(function () use ($media) {
            $m = Media::lockForUpdate()->findOrFail($media->id);
            if ($m->products()->exists() || Variant::where('media_id', $m->id)->exists() || ContentBlock::where('media_id', $m->id)->exists() || Setting::whereIn('key', ['logo_media_id', 'social_media_id'])->where('value', (string) $m->id)->exists()) {
                throw ValidationException::withMessages(['media' => 'Remove all references before deleting this image.']);
            }
            $m->delete();
            DB::afterCommit(fn () => Storage::disk($m->disk)->delete($m->path));
        });
    }
}
