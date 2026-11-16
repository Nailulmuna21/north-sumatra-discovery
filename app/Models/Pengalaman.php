<?php

namespace App\Models;

use App\Models\Concerns\PunyaUrlGambar;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Pengalaman extends Model
{
    use PunyaUrlGambar;

    protected $table = 'pengalaman';

    public const JENIS = ['kuliner', 'destinasi'];
    public const MAKS_FOTO = 4;

    protected $fillable = ['nama', 'jenis', 'nama_tempat', 'rating', 'teks', 'foto', 'email', 'daerah_id'];

    protected static function booted(): void
    {
        // Baris foto ikut terhapus lewat cascade di database; berkas di disk harus dihapus di sini
        static::deleting(function (Pengalaman $p) {
            foreach ($p->fotos()->pluck('path') as $path) {
                if ($path && ! preg_match('#^https?://#i', $path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        });
    }

    public function daerah(): BelongsTo
    {
        return $this->belongsTo(Daerah::class);
    }

    /** Foto kiriman, urut dari sampul (urutan 0) */
    public function fotos(): HasMany
    {
        return $this->hasMany(PengalamanFoto::class)->orderBy('urutan')->orderBy('id');
    }

    public function suka(): HasMany
    {
        return $this->hasMany(PengalamanSuka::class);
    }

    /** $pengalaman->foto_url (path di disk 'public' diubah jadi URL /storage/...) */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->urlDari($this->foto));
    }

    /** @return list<string> URL semua foto (kosong bila kiriman tanpa foto). Pakai with('fotos') agar tanpa N+1 */
    protected function fotoUrls(): Attribute
    {
        return Attribute::get(function () {
            if ($this->relationLoaded('fotos') && $this->fotos->isNotEmpty()) {
                return $this->fotos->map->url->filter()->values()->all();
            }

            return array_values(array_filter([$this->foto_url]));   // data lama / cadangan: kolom sampul
        });
    }
}
