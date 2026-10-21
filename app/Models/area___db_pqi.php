<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class area___db_pqi extends Model
{
    public $timestamps = true;

    protected $table = 'area___db_pqi';

    protected $guarded = [];

    public function bagian_data()
    {
        return $this->belongsTo(database___bagian::class, 'id_bagian');
    }

    public function location_data()
    {
        return $this->belongsTo(database___location::class, 'id_location');
    }

    public function area_data()
    {
        return $this->belongsTo(database___area::class, 'id_area');
    }
}
