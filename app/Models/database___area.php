<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class database___area extends Model
{
    public $timestamps = true;

    protected $table = 'database___area';

    protected $guarded = [];

    public function getIdAttribute($value)
    {
        return trim($value);
    }

    public function location()
    {
        return $this->belongsTo(database___location::class, 'id_location');
    }

    public function area_data()
    {
        return $this->hasMany(area___db::class, 'area');
    }
    public function projectCells()
    {
        return $this->hasMany(project___cell_model::class, 'cell', 'id');
    }
}
