<?php

namespace Cookbook\Picture;

use Intervention\Image\ImageManagerStatic as Image;

/**
 * Description of Image
 *
 * @author AlexK
 */
class Picture
{
    private $path;
    private $file;
    private $permissions = '0771';
    private $imgSize = ['w' => 1024, 'h' => 768];
    private $tbnSize = ['w' => 362, 'h' => 238];
    
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function uploadImage()
    {
        if (! is_dir($this->path)) {
            mkdir($this->path, intval($this->permissions, 8), true);
        } else {
            chmod($this->path, intval($this->permissions, 8));
        }

        $id = uniqid();

        Image::make($this->file)->save($this->path . $id . '.jpg');

        if (Image::make($this->file)->height() > Image::make($this->file)->width()) {
            Image::make($this->file)->heighten($this->imgSize['h'], function ($constraint) {$constraint->upsize();})->save($this->path . $id . '_lg.jpg');
            Image::make($this->file)->heighten($this->tbnSize['h'], function ($constraint) {$constraint->upsize();})->save($this->path . $id . '_tn.jpg');
        } else {
            Image::make($this->file)->widen($this->imgSize['w'], function ($constraint) {$constraint->upsize();})->save($this->path . $id . '_lg.jpg');
            Image::make($this->file)->widen($this->tbnSize['w'], function ($constraint) {$constraint->upsize();})->save($this->path . $id . '_tn.jpg');
        }

        return $id;
    }

    public function deleteImages()
    {
        if ($this->file != '') {
            $this->file = strpos($this->file, ',') !== false
                ? array_filter(explode(',', $this->file))
                : [$this->file];
        }

        foreach ($this->file as $value) {
            $img = $this->path . $value . '.jpg';
            $lg = $this->path . $value . '_lg.jpg';
            $tbn = $this->path . $value . '_tn.jpg';

            if (file_exists($img)) {
                unlink($img);
            }

            if (file_exists($lg)) {
                unlink($lg);
            }

            if (file_exists($tbn)) {
                unlink($tbn);
            }
        }
    }
}