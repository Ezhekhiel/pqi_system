<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class database___bagian extends Model
{
    public $timestamps = true;

    protected $table = 'database___bagian';

    // Amankan kolom 'id' agar tidak bisa diisi eksplisit
    protected $guarded = ['id'];

    /**
     * Otomatis memotong spasi (trim) sebelum data disimpan/diupdate
     */
    protected static function booted()
    {
        static::saving(function ($model) {
            // Cek id hanya jika diisi dan bukan null (misal saat UPDATE)
            if (!is_null($model->getAttributes()['id'] ?? null)) {
                $model->id = trim($model->id);
            }
            if (isset($model->id_location)) {
                $model->id_location = trim($model->id_location);
            }
            if (isset($model->id_area)) {
                $model->id_area = trim($model->id_area);
            }
        });
    }

    /**
     * Accessor: Memotong spasi saat kolom dibaca dari database
     * (Hapus getIdAttribute agar tidak mengubah null menjadi string kosong)
     */
    public function getIdLocationAttribute($value)
    {
        return is_null($value) ? null : trim($value);
    }

    public function getIdAreaAttribute($value)
    {
        return is_null($value) ? null : trim($value);
    }

    /**
     * Definisi Relasi Eloquent
     */
    public function bagian_data()
    {
        return $this->hasMany(area___db::class, 'bagian');
    }

    public function bagian_data_iot()
    {
        return $this->hasMany(area_iot___db::class, 'id_bagian', 'id');
    }

    public function location_data()
    {
        return $this->belongsTo(database___location::class, 'id_location', 'id');
    }

    public function area()
    {
        return $this->belongsTo(database___area::class, 'id_area', 'id');
    }
}
