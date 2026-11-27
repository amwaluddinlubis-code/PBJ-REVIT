<?php

/**
 * Select yang dapat dicari (searchable dropdown ala Preline Advanced Select).
 *
 * Merender <select> asli bertanda data-select-cari; berkas select-cari.js
 * mengubahnya di peramban menjadi dropdown dengan kotak pencarian, navigasi
 * papan tik, dan tanda centang pada pilihan aktif. Select asli tetap tinggal
 * di formulir (tersembunyi) sehingga nama/nilai yang terkirim dan peristiwa
 * change tidak berubah — kode lama yang membaca select ini tetap bekerja.
 *
 * Select yang ditulis tangan pun bisa: cukup tambahkan atribut
 * data-select-cari (dan data-placeholder bila perlu) pada <select> biasa.
 *
 * @param array $options Daftar opsi: baris ['value' => .., 'label' => ..,
 *                       'data' => ['harga' => 123]] atau peta nilai => label.
 * @param mixed $selected Nilai terpilih awal.
 * @param array $attrs    Atribut select tambahan (id, class, required, …);
 *                        kunci 'placeholder' menjadi label opsi kosong.
 */
function select_cari(string $name, array $options, $selected = null, array $attrs = []): string
{
    $placeholder = (string) ($attrs['placeholder'] ?? '— pilih —');
    unset($attrs['placeholder']);

    $html = '<select name="' . esc($name, 'attr') . '" data-select-cari data-native-select data-placeholder="' . esc($placeholder, 'attr') . '"';
    foreach ($attrs as $k => $v) {
        if ($v === true) {
            $html .= ' ' . esc($k, 'attr');
        } elseif ($v !== false && $v !== null) {
            $html .= ' ' . esc($k, 'attr') . '="' . esc((string) $v, 'attr') . '"';
        }
    }
    $html .= '><option value="">' . esc($placeholder) . '</option>';

    foreach ($options as $k => $o) {
        if (is_array($o)) {
            $value = (string) ($o['value'] ?? '');
            $label = (string) ($o['label'] ?? $value);
            $data  = (array) ($o['data'] ?? []);
        } else {
            $value = (string) $k;
            $label = (string) $o;
            $data  = [];
        }
        $html .= '<option value="' . esc($value, 'attr') . '"'
            . ($selected !== null && (string) $selected === $value ? ' selected' : '');
        foreach ($data as $dk => $dv) {
            $html .= ' data-' . esc((string) $dk, 'attr') . '="' . esc((string) $dv, 'attr') . '"';
        }
        $html .= '>' . esc($label) . '</option>';
    }

    return $html . '</select>';
}
