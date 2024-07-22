<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MultiImages extends Model
{
    use HasFactory;

    protected $table = "multi_images";

    protected $appends = ['image_path_link'];

    public function getImagePathLinkAttribute()
    {
        return $this->image_path ? asset('public/' . $this->image_path) : null;
    }
}
