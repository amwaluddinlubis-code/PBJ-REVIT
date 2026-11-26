<?php

namespace App\Libraries;

/**
 * Konverter Markdown ringkas untuk halaman Panduan dalam aplikasi.
 * Hanya mendukung konstruksi yang dipakai docs/Panduan_Penggunaan.md:
 * judul, paragraf, tebal/miring/kode sebaris, tautan, blok kode,
 * daftar (termasuk bersarang dasar), tabel, kutipan, dan garis pemisah.
 * Semua teks sumber diescape lebih dulu; tautan hanya diterima untuk
 * skema http/https/mailto, jangkar (#), dan path relatif.
 */
class MarkdownMini
{
    /** Daftar bab (judul level 2) untuk daftar isi: [['judul'=>..., 'slug'=>...], ...]. */
    public static function daftarBab(string $md): array
    {
        $bab = [];
        foreach (preg_split('/\R/', $md) as $baris) {
            if (preg_match('/^##\s+(.+?)\s*$/', $baris, $m)) {
                $judul = trim($m[1]);
                $bab[] = ['judul' => $judul, 'slug' => self::slug($judul)];
            }
        }
        return $bab;
    }

    public static function slug(string $teks): string
    {
        $s = mb_strtolower(trim($teks));
        $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s) ?? '';
        return trim($s, '-');
    }

    public static function keHtml(string $md): string
    {
        $baris    = preg_split('/\R/', str_replace("\r\n", "\n", $md));
        $html     = [];
        $para     = [];
        $daftar   = [];   // tumpukan daftar terbuka: ['jenis'=>'ul'|'ol', 'indent'=>int]
        $kode     = null; // null = tidak di blok kode; string = isi terkumpul
        $tabel    = [];   // baris-baris tabel terkumpul (array sel)
        $kutip    = [];

        $tutupPara = static function () use (&$para, &$html): void {
            if ($para !== []) {
                $html[] = '<p>' . self::sebaris(implode(' ', $para)) . '</p>';
                $para   = [];
            }
        };
        $tutupDaftar = static function () use (&$daftar, &$html): void {
            while ($daftar !== []) {
                $html[] = '</li></' . array_pop($daftar)['jenis'] . '>';
            }
        };
        $tutupTabel = static function () use (&$tabel, &$html): void {
            if ($tabel === []) {
                return;
            }
            $kepala = array_shift($tabel);
            $out    = '<div class="bungkus-tabel"><table><thead><tr>';
            foreach ($kepala as $sel) {
                $out .= '<th>' . self::sebaris($sel) . '</th>';
            }
            $out .= '</tr></thead><tbody>';
            foreach ($tabel as $r) {
                $out .= '<tr>';
                foreach ($r as $sel) {
                    $out .= '<td>' . self::sebaris($sel) . '</td>';
                }
                $out .= '</tr>';
            }
            $html[] = $out . '</tbody></table></div>';
            $tabel  = [];
        };
        $tutupKutip = static function () use (&$kutip, &$html): void {
            if ($kutip !== []) {
                $html[] = '<blockquote><p>' . self::sebaris(implode(' ', $kutip)) . '</p></blockquote>';
                $kutip  = [];
            }
        };
        $tutupSemua = static function () use ($tutupPara, $tutupDaftar, $tutupTabel, $tutupKutip): void {
            $tutupPara();
            $tutupDaftar();
            $tutupTabel();
            $tutupKutip();
        };

        foreach ($baris as $b) {
            // Blok kode berpagar.
            if ($kode !== null) {
                if (preg_match('/^```/', $b)) {
                    $html[] = '<pre><code>' . esc($kode) . '</code></pre>';
                    $kode   = null;
                } else {
                    $kode .= $b . "\n";
                }
                continue;
            }
            if (preg_match('/^```/', $b)) {
                $tutupSemua();
                $kode = '';
                continue;
            }
            // Baris kosong menutup paragraf/kutipan (daftar dibiarkan terbuka
            // agar daftar longgar tetap menyatu; ditutup oleh blok berikutnya).
            if (trim($b) === '') {
                $tutupPara();
                $tutupKutip();
                continue;
            }
            // Judul.
            if (preg_match('/^(#{1,4})\s+(.+?)\s*$/', $b, $m)) {
                $tutupSemua();
                $lvl    = strlen($m[1]);
                $teks   = trim($m[2]);
                $html[] = '<h' . $lvl . ' id="' . esc(self::slug($teks)) . '">' . self::sebaris($teks) . '</h' . $lvl . '>';
                continue;
            }
            // Garis pemisah.
            if (preg_match('/^\s*(-{3,}|\*{3,})\s*$/', $b)) {
                $tutupSemua();
                $html[] = '<hr>';
                continue;
            }
            // Tabel: baris diawali/diakhiri pipa; baris pemisah |---| dilewati.
            if (preg_match('/^\s*\|.*\|\s*$/', $b)) {
                if (preg_match('/^\s*\|[\s:|-]+\|\s*$/', $b)) {
                    continue;
                }
                $tutupPara();
                $tutupDaftar();
                $tutupKutip();
                $sel     = array_map('trim', explode('|', trim(trim($b), '|')));
                $tabel[] = $sel;
                continue;
            }
            $tutupTabel();
            // Kutipan.
            if (preg_match('/^>\s?(.*)$/', $b, $m)) {
                $tutupPara();
                $tutupDaftar();
                $kutip[] = $m[1];
                continue;
            }
            $tutupKutip();
            // Daftar: "- ", "* ", atau "1. " dengan indentasi spasi.
            if (preg_match('/^(\s*)([-*]|\d+[.)])\s+(.*)$/', $b, $m)) {
                $tutupPara();
                $indent = strlen($m[1]);
                $jenis  = preg_match('/^\d/', $m[2]) ? 'ol' : 'ul';
                $isi    = $m[3];
                while ($daftar !== [] && end($daftar)['indent'] > $indent) {
                    $html[] = '</li></' . array_pop($daftar)['jenis'] . '>';
                }
                if ($daftar === [] || end($daftar)['indent'] < $indent) {
                    $html[]   = '<' . $jenis . '>';
                    $daftar[] = ['jenis' => $jenis, 'indent' => $indent];
                } elseif (end($daftar)['jenis'] !== $jenis) {
                    $html[]   = '</li></' . array_pop($daftar)['jenis'] . '><' . $jenis . '>';
                    $daftar[] = ['jenis' => $jenis, 'indent' => $indent];
                } else {
                    $html[] = '</li>';
                }
                $html[] = '<li>' . self::sebaris($isi);
                continue;
            }
            // Baris lanjutan daftar (indentasi tanpa penanda) digabung ke item.
            if ($daftar !== [] && preg_match('/^\s{2,}(\S.*)$/', $b, $m)) {
                $html[count($html) - 1] .= ' ' . self::sebaris(trim($m[1]));
                continue;
            }
            $tutupDaftar();
            $para[] = trim($b);
        }
        if ($kode !== null) {
            $html[] = '<pre><code>' . esc($kode) . '</code></pre>';
        }
        $tutupSemua();

        return implode("\n", $html);
    }

    /** Format sebaris: kode, tautan, tebal, miring — sesudah escape HTML. */
    private static function sebaris(string $teks): string
    {
        $s = esc($teks);

        // Kode sebaris dilindungi dari format lain lewat penanda sementara.
        $kode = [];
        $s    = preg_replace_callback('/`([^`]+)`/', static function (array $m) use (&$kode): string {
            $kode[] = '<code>' . $m[1] . '</code>';
            return "\x00" . (count($kode) - 1) . "\x00";
        }, $s) ?? $s;

        // Tautan [teks](url): hanya skema aman, jangkar, atau path relatif.
        $s = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', static function (array $m): string {
            $url = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            if (preg_match('#^(https?://|mailto:|#|/)#i', $url) || ! preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
                return '<a href="' . esc($url) . '">' . $m[1] . '</a>';
            }
            return $m[1];
        }, $s) ?? $s;

        $s = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $s) ?? $s;

        return preg_replace_callback("/\x00(\d+)\x00/", static fn (array $m): string => $kode[(int) $m[1]] ?? '', $s) ?? $s;
    }
}
