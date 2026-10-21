<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class database___location extends Model
{
    public $timestamps = true;

    protected $table = 'database___location';

    protected $guarded = [];

    public function getIdAttribute($value)
    {
        return trim($value);
    }

    public function location_data()
    {
        return $this->hasMany(area___db::class, 'location');
    }

}
