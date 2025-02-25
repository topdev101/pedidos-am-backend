<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;

class ProcessImageUploads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $disk;
    protected $filePath;
    protected $imageContent;

    public function __construct($disk, $filePath, $imageContent)
    {
        $this->disk = $disk;
        $this->filePath = $filePath;
        $this->imageContent = $imageContent;
    }

    public function handle()
    {
        $imageData = base64_decode($this->imageContent);
        Storage::disk($this->disk)->put($this->filePath, $imageData, 'public');
    }
}