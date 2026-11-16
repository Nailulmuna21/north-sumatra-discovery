<?php

namespace App\Http\Requests;

use App\Models\Pengalaman;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PengalamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Wajib login (route juga sudah dilindungi middleware auth); nama diambil dari akun, bukan dari form
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(Pengalaman::JENIS)],
            'nama_tempat' => ['required', 'string', 'max:150'],
            'daerah_id' => ['required', 'integer', 'exists:daerah,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'cerita' => ['required', 'string', 'max:2000'],
            // Foto opsional, maks 4. Validasi di SERVER wajib: JPG/PNG/WEBP, maks 5 MB per foto
            'foto' => ['nullable', 'array', 'max:'.Pengalaman::MAKS_FOTO],
            'foto.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis.required' => 'Pilih Kuliner atau Destinasi.',
            'jenis.in' => 'Jenis harus Kuliner atau Destinasi.',
            'nama_tempat.required' => 'Tulis nama kuliner atau destinasinya.',
            'nama_tempat.max' => 'Nama maksimal 150 karakter.',
            'daerah_id.required' => 'Pilih daerah.',
            'daerah_id.exists' => 'Daerah tidak valid.',
            'rating.required' => 'Beri rating dulu.',
            'rating.between' => 'Rating harus antara 1 sampai 5.',
            'cerita.required' => 'Ceritakan pengalamanmu.',
            'cerita.max' => 'Cerita maksimal 2000 karakter.',
            'foto.max' => 'Foto maksimal '.Pengalaman::MAKS_FOTO.' buah.',
            'foto.*.mimes' => 'File harus berupa gambar (JPG, PNG, WEBP).',
            'foto.*.max' => 'Ukuran tiap foto maksimal 5MB.',
            'foto.*.uploaded' => 'Unggahan gagal. Pastikan tiap foto maksimal 5MB.',
        ];
    }
}
