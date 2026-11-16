<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 | v2 — Pengalaman menjadi kiriman "ala media sosial":
 |   - jenis (kuliner | destinasi), nama_tempat, rating menjadi kolom sendiri
 |     (dulu digabung dalam satu teks: "📍 tempat — ★★★☆☆ + cerita" lalu diurai dengan regex)
 |   - kolom `teks` sekarang berisi CERITA saja
 |   - foto boleh 0..4 -> tabel pengalaman_foto; kolom `foto` dipertahankan sebagai sampul (foto pertama)
 | Data lama dipindahkan otomatis (backfill) di bawah, jadi tidak ada cerita yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengalaman', function (Blueprint $table) {
            $table->string('jenis', 20)->default('destinasi')->after('nama');
            $table->string('nama_tempat', 150)->nullable()->after('jenis');
            $table->unsignedTinyInteger('rating')->nullable()->after('nama_tempat');
            $table->string('foto')->nullable()->change();   // foto kini opsional
            $table->index(['jenis', 'created_at']);
        });

        Schema::create('pengalaman_foto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengalaman_id')->constrained('pengalaman')->cascadeOnDelete();
            $table->string('path');                              // path di disk 'public'
            $table->unsignedTinyInteger('urutan')->default(0);   // 0 = sampul
            $table->timestamps();
            $table->index(['pengalaman_id', 'urutan']);
        });

        // ---- Backfill data lama ----
        DB::table('pengalaman')->orderBy('id')->chunkById(100, function ($baris) {
            foreach ($baris as $p) {
                $update = [];
                $bagian = preg_split('/\R{2,}/u', trim((string) $p->teks), 2);

                if (count($bagian) === 2 && str_starts_with($bagian[0], '📍')) {
                    $kepala = trim(mb_substr($bagian[0], mb_strlen('📍')));
                    if (preg_match('/^(.*?)\s*(★*)(☆*)\s*$/u', $kepala, $m)) {
                        $tempat = rtrim($m[1], " \t—-");
                        $bintang = mb_strlen($m[2]);
                    } else {
                        $tempat = $kepala;
                        $bintang = 0;
                    }
                    $update = [
                        'nama_tempat' => mb_substr($tempat, 0, 150) ?: null,
                        'rating' => $bintang >= 1 && $bintang <= 5 ? $bintang : null,
                        'teks' => $bagian[1],
                    ];
                }

                if ($update) {
                    DB::table('pengalaman')->where('id', $p->id)->update($update);
                }

                if (! empty($p->foto)) {
                    DB::table('pengalaman_foto')->insert([
                        'pengalaman_id' => $p->id, 'path' => $p->foto, 'urutan' => 0,
                        'created_at' => $p->created_at, 'updated_at' => $p->updated_at,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengalaman_foto');

        Schema::table('pengalaman', function (Blueprint $table) {
            $table->dropIndex(['jenis', 'created_at']);
            $table->dropColumn(['jenis', 'nama_tempat', 'rating']);
        });
        // catatan: kolom `foto` dibiarkan nullable, dan `teks` tidak digabung kembali ke format lama
    }
};
