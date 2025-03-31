<?php

namespace App\Services\FileHandle;

use App\Services\Service;
use App\Core\Services\FileHandle\Contracts\ImageProcessContract;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Decoders\FilePathImageDecoder;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class ImageProcessService extends Service implements ImageProcessContract
{
    private int $resize_width = 2048;
    private Image $image;
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(Driver::class);
    }

    public function make(UploadedFile $file):UploadedFile
    {
        // TODO: Implement make() method.
        $extension = $file->extension();
        $path = $file->path();
        $org_name = $file->getClientOriginalName();

        if ($this->_is_image($extension)) {
            $this->_make_image($file);
            if ($this->_is_heic($extension)) {
                $this->_heic_to_jpg($path,$org_name);
            }
            $this->_resize();
            $file = new UploadedFile($path, $org_name);
        }
        return $file;
    }

    protected function _is_image(string $extension): bool
    {
        return $this->manager->driver()->supports($extension);
    }

    protected function _is_heic(string $extension): bool
    {
        return $extension === 'heic';
    }

    protected function _make_image(UploadedFile $file):void
    {
        $this->image = $this->manager->read($file->path(),FilePathImageDecoder::class)->orient();
    }

    protected function _heic_to_jpg(string $path,string $org_name):void
    {
        $this->image->toJpeg(100)->save($path);
        $this->_make_image(new UploadedFile($path,$org_name));
    }

    protected function _resize():void
    {
        ($this->image->size()->width() >= $this->resize_width) ?? $this->image->scale(width:$this->resize_width);
    }
}
