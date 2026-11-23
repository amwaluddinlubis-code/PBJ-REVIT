<?php

namespace Tests\Unit;

use App\Libraries\AksesMenu;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Penjaga konsistensi dokumentasi & sidebar: katalog menu harus tercermin di
 * sidebar, dan tautan/gambar markdown harus menunjuk berkas yang ada.
 */
class DocsConsistencyTest extends CIUnitTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = rtrim(realpath(HOMEPATH), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function testSidebarMencakupSemuaKunciAksesMenu(): void
    {
        $sidebar = file_get_contents($this->root . 'app/Views/layout/main.php');
        $this->assertNotFalse($sidebar);

        foreach (AksesMenu::semuaKunci() as $kunci) {
            $this->assertMatchesRegularExpression(
                "/base_url\(['\"]" . preg_quote($kunci, '/') . "['\"]\)/",
                $sidebar,
                "Kunci menu AksesMenu '{$kunci}' tidak ditemukan di sidebar (layout/main.php)."
            );
        }
    }

    public function testGambarMarkdownAda(): void
    {
        $berkas = $this->berkasMarkdown();
        $hilang = [];
        foreach ($berkas as $f) {
            $isi = file_get_contents($f);
            if (preg_match_all('/!\[[^\]]*\]\(([^)]+)\)/', $isi, $m)) {
                foreach ($m[1] as $target) {
                    $target = trim($target);
                    if ($target === '' || str_starts_with($target, 'http://') || str_starts_with($target, 'https://')) {
                        continue;
                    }
                    $path = $this->root . ltrim(strtok($target, '#?'), '/');
                    if (! file_exists($path)) {
                        $hilang[] = basename($f) . ' -> ' . $target;
                    }
                }
            }
        }
        $this->assertSame([], $hilang, "Gambar markdown yang tidak ada: \n" . implode("\n", $hilang));
    }

    public function testTautanMarkdownLokalAda(): void
    {
        $berkas = $this->berkasMarkdown();
        $hilang = [];
        foreach ($berkas as $f) {
            $isi = file_get_contents($f);
            if (preg_match_all('/\[[^\]]*\]\(([^)]+)\)/', $isi, $m)) {
                foreach ($m[1] as $target) {
                    $target = trim($target);
                    if ($target === '' || str_starts_with($target, 'http://') || str_starts_with($target, 'https://')
                        || str_starts_with($target, 'mailto:') || str_starts_with($target, '#')) {
                        continue;
                    }
                    $target = strtok($target, '#?');
                    if ($target === '' || $target === false) {
                        continue;
                    }
                    $path = $this->root . ltrim($target, '/');
                    if (! file_exists($path) && ! is_dir($path)) {
                        $hilang[] = basename($f) . ' -> ' . $target;
                    }
                }
            }
        }
        $this->assertSame([], $hilang, "Tautan markdown lokal yang tidak ada: \n" . implode("\n", $hilang));
    }

    /** @return string[] */
    private function berkasMarkdown(): array
    {
        $daftar = glob($this->root . '*.md') ?: [];
        $daftar = array_merge($daftar, glob($this->root . 'docs/*.md') ?: []);
        return array_values(array_unique($daftar));
    }
}