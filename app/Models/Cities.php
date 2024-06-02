<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cities extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $fillable = ['country_id','name'];

    protected $hidden = ['created_at', 'updated_at'];

    // protected $appends = ['country_name'];

    public function country()
    {
        return $this->belongsTo(Countries::class, 'country_id');
    }

    public function getCountryNameAttribute()
    {
        return $this->country ? $this->country->name : null;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'countryId' => $this->country_id,
            'country' => $this->country_name,
            'city' => $this->name,
        ];
    }
}
