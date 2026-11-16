<?php

namespace App\Models;

use App\Models\Concerns\PunyaUrlGambar;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengalamanFoto extends Model
{
    use PunyaUrlGambar;

    protected $table = 'pengalaman_foto';

    protected $fillable = ['pengalaman_id', 'path', 'urutan'];

    public function pengalaman(): BelongsTo
    {
        return $this->belongsTo(Pengalaman::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => $this->urlDari($this->path));
    }
}
