<?php
$rahasia = 'git-6c30';
if (($_GET['k'] ?? '') !== $rahasia) {
    http_response_code(404);
    exit('tidak ada');
}
$akar = dirname(__DIR__);
$git = '"C:\\Program Files\\Git\\cmd\\git.exe" -c safe.directory=* -C ' . escapeshellarg($akar) . ' ';
$op = $_GET['op'] ?? 'cek';

function jalan(string $cmd): array
{
    $keluaran = [];
    $kode = 0;
    exec($cmd . ' 2>&1', $keluaran, $kode);
    return ['kode' => $kode, 'keluaran' => trim(implode("\n", $keluaran))];
}

header('Content-Type: application/json');

if ($op === 'cek') {
    $hasil = [];
    foreach ([
        'status' => 'status --short',
        'check' => 'diff --check',
        'stat' => 'diff --stat',
        'cabang' => 'status -sb',
        'remote' => 'remote -v',
        'terakhir' => 'log -1 --format=%h',
        'penulis' => 'log -1 --format=%an',
        'surel' => 'log -1 --format=%ae',
    ] as $nama => $cmd) {
        $hasil[$nama] = jalan($git . $cmd);
    }
    echo json_encode($hasil, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($op === 'diff') {
    $jalur = $_GET['f'] ?? '';
    echo jalan($git . 'diff -- ' . escapeshellarg($jalur))['keluaran'];
    exit;
}

if ($op === 'bersih') {
    $hasil = [];
    foreach (glob($akar . '/public/uji-*.html') as $f) {
        $hasil[basename($f)] = unlink($f) ? 'terhapus' : 'GAGAL';
    }
    foreach (glob($akar . '/public/_*.php') as $f) {
        if (basename($f) !== basename(__FILE__)) {
            $hasil[basename($f)] = unlink($f) ? 'terhapus' : 'GAGAL';
        }
    }
    echo json_encode($hasil, JSON_PRETTY_PRINT);
    exit;
}

if ($op === 'commit') {
    $pesan = file_get_contents('php://input');
    $berkas = $akar . '/.pesan-commit-sementara';
    file_put_contents($berkas, $pesan);
    $daftar = 'AGENTS.md app/Common.php app/Views/bahan/index.php docs/Desain_Token.md docs/ui-design-review.md public/assets/app.css public/assets/paginasi.js';
    $hasil = [
        'add' => jalan($git . 'add -- ' . $daftar),
        'staged' => jalan($git . 'diff --cached --stat'),
        'commit' => jalan($git . 'commit -F ' . escapeshellarg($berkas)),
    ];
    @unlink($berkas);
    echo json_encode($hasil, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($op === 'push') {
    echo json_encode(jalan($git . 'push'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo 'op tidak dikenal';
