<?php

namespace App\Models;

use App\Traits\UploadAble;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use UploadAble;

    protected $guarded = [];

    protected $appends = ['src'];

    public function imageable(): MorphTo {
        return $this->morphTo();
    }

    public function getSrcAttribute() {
        if (!$this->path) return '';
        return Storage::disk($this->disk)->url($this->path);
    }

    public function deleteImage() {
        if ($this->fileExists($this->path, $this->disk)) {
            $this->deleteFile($this->path, $this->disk);
        }
        $this->delete();
    }
}
