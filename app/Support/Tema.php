<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Warna tema dari "Pengaturan Web" → tab "Warna Tema". Kelas Tailwind brand-navy* dan
 * brand-gold* membaca variabel CSS, sehingga warna dapat diganti per instansi tanpa build ulang.
 */
class Tema
{
    public const PRIMER = '#0b2545';

    public const AKSEN = '#d4af37';

    public const HEX_REGEX = '/^#[0-9a-fA-F]{6}$/';

    public static function valid(?string $hex): bool
    {
        return is_string($hex) && preg_match(static::HEX_REGEX, $hex) === 1;
    }

    public static function primer(): string
    {
        $warna = Setting::get('theme_primary_color');

        return static::valid($warna) ? strtolower($warna) : static::PRIMER;
    }

    public static function aksen(): string
    {
        $warna = Setting::get('theme_accent_color');

        return static::valid($warna) ? strtolower($warna) : static::AKSEN;
    }

    /**
     * Campur warna ke putih (jumlah positif) atau hitam (negatif), 0–1.
     */
    public static function campur(string $hex, float $jumlah): string
    {
        $tujuan = $jumlah >= 0 ? 255 : 0;
        $jumlah = abs($jumlah);

        return '#'.collect(str_split(ltrim($hex, '#'), 2))
            ->map(fn (string $kanal) => sprintf('%02x', (int) (hexdec($kanal) + ($tujuan - hexdec($kanal)) * $jumlah)))
            ->implode('');
    }

    /**
     * Variabel CSS brand-navy* dan brand-gold*; palet bawaan dipakai persis bila warna belum diubah.
     *
     * @return array<string, string>
     */
    public static function variabel(): array
    {
        $primer = static::primer();
        $aksen = static::aksen();

        return [
            '--color-brand-navy' => $primer,
            '--color-brand-navy-light' => $primer === static::PRIMER ? '#13315c' : static::campur($primer, 0.12),
            '--color-brand-navy-dark' => $primer === static::PRIMER ? '#071a33' : static::campur($primer, -0.35),
            '--color-brand-gold' => $aksen,
            '--color-brand-gold-light' => static::campur($aksen, 0.25),
        ];
    }
}
