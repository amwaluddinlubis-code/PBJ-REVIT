<?php

namespace App\Filters;

use App\Libraries\AksesMenu;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Config\Services;

/**
 * Penegak akses menu per peran (pengaturan di Pengaturan > Akses Menu).
 * Berjalan global sesudah csrf: tamu dan admin selalu lolos; segmen yang
 * tidak ada di katalog menu juga lolos. Menu yang dicabut untuk peran
 * pengguna ditolak di sini, bukan hanya disembunyikan dari sidebar.
 * Gerbang peran penjagaPeran() pada aksi tertentu tetap berlaku seperti
 * biasa; filter ini lapisan tambahan di tingkat menu.
 */
class AksesMenuFilter implements FilterInterface
{
    /** Segmen pertama yang tidak pernah dibatasi filter ini. */
    private const SELALU = ['', 'profil', 'login', 'logout'];

    public function before(RequestInterface $request, $arguments = null)
    {
        $peran = (string) (session()->get('pengguna_peran') ?? '');
        if ($peran === '' || $peran === 'admin') {
            return;
        }
        $seg = $request->getUri()->getSegments();
        if (($seg[0] ?? '') === 'index.php') {
            array_shift($seg);
        }
        $pertama = $seg[0] ?? '';
        $kedua   = $seg[1] ?? '';
        if (in_array($pertama, self::SELALU, true)) {
            return;
        }
        // Pengalih sekolah aktif harus selalu bisa dipakai.
        if ($pertama === 'sekolah' && $kedua === 'pilih') {
            return;
        }
        if (! in_array($pertama, AksesMenu::semuaKunci(), true)) {
            return;
        }
        $peta = AksesMenu::petaUntukPeran(Database::connect(), $peran);
        if ($peta[$pertama] ?? true) {
            return;
        }
        if ($request->isAJAX()) {
            $res = Services::response();
            $res->setStatusCode(403);
            $res->setBody('Menu ini tidak tersedia untuk peran Anda.');
            return $res;
        }
        return redirect()->to('/')->with('galat', 'Menu tersebut tidak tersedia untuk peran Anda (' . $peran . '). Hubungi admin bila Anda memerlukannya.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
