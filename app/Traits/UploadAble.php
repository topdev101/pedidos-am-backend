<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use InterventionImage;

/**
* Trait UploadAble
* @package App\Traits
*/
trait UploadAble
{
    /**
     * @param UploadedFile $file
     * @param null $folder
     * @param string $disk
     * @param null $filename
     * @return false|string
     */
    public function uploadFile(UploadedFile $file, $folder = null, $disk = 'public', $filename = null)
    {
        $name = !is_null($filename) ? $filename : Str::random(25);

        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageUpload = InterventionImage::make($file);
            if ($imageUpload->height() > 1650) {
                $imageUpload->heighten(1650, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->orientate();
            }
            $imageUpload->encode('webp', 60);
            $fileName = $name . ".webp";
            $filePath = "/$folder/$fileName";

            Storage::disk($disk)->put($filePath, $imageUpload->stream(), 'public');

            return $filePath;
        } else {
            $extension = $file->getClientOriginalExtension();
            return $file->storePubliclyAs(
                $folder,
                $name . "." . $extension,
                $disk
            );
        }
    }

    /**
     * @param UploadedFile $file
     * @param null $folder
     * @param string $disk
     * @param null $filename
     * @return false|string
     */
    public function uploadThumbnail(UploadedFile $file, $folder = null, $disk = 'public', $filename = null)
    {
        $name = !is_null($filename) ? $filename : Str::random(25);
        $imageUpload = InterventionImage::make($file);
        if ($imageUpload->height() > 170) {
            $imageUpload->resize(null, 170, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->orientate();
        }
        $imageUpload->encode('webp', 60);
        $fileName = $name . ".webp";
        $filePath = "/$folder/$fileName";

        Storage::disk($disk)->put($filePath, $imageUpload->stream(), 'public');

        return $filePath;
    }

    /**
     * @param $files,
     * @param $filename
     * @param $folder
     * @param $imageable
     * @return void
     */
    public function uploadFiles($files, $filename, $folder, $imageable) {
        $disk = config('filesystems.image_storage_disk');
        foreach ($files as $key => $image) {
            $path = $this->uploadFile($image, $folder, $disk, $filename . '_' . date('YmdHis') . '_' . $key);
            $mimeType = 'file';
            if (str_starts_with($image->getMimeType(), 'image/')) {
                $mimeType = 'image';
            } else if (str_starts_with($image->getMimeType(), 'video/')) {
                $mimeType = 'video';
            }
            $imageable->images()->create([
                'mime' => $mimeType,
                'disk' => $disk,
                'path' => $path,
            ]);
        }
    }

    /**
     * @param null $path
     * @param string $disk
     */
    public function deleteFile($path = '', $disk = 'public')
    {
        if (strpos($path, '/') === 0) {
            $path = ltrim($path, '/');
        }
        Storage::disk($disk)->delete($path);
    }

    /**
     * @param null $path
     * @param string $disk
     */
    public function fileExists($path = '', $disk = 'public')
    {
        if (strpos($path, '/') === 0) {
            $path = ltrim($path, '/');
        }
        return Storage::disk($disk)->exists($path);
    }
}

