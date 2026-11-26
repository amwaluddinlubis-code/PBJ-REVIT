// Komponen Alpine generik untuk hapus massal (bulk) di halaman daftar:
// centang baris -> tombol "Hapus Terpilih (n)" -> formulir terkirim dengan
// ids[] dan pesan konfirmasi yang menyebut jumlah terpilih ({n}).
// Pakai: <div data-bulk-akar x-data="komponenBulk('form-bulk-x','ids-x')">
//   kotak centang baris: <input type="checkbox" data-pilih value="ID" x-model="terpilih">
//   kotak centang kepala: <input type="checkbox" @change="pilihSemua($event)">
//   tombol: <button type="button" :disabled="terpilih.length===0"
//             @click="kirim('Hapus {n} baris terpilih?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
// Aksi massal kedua (non-hapus) boleh memakai formulir lain:
//   @click="kirim('Buat SPK dari {n} baris terpilih?', 'form-lain', 'ids-lain')"
// Kotak centang nonaktif (disabled) tidak ikut terpilih oleh pilihSemua.
window.komponenBulk = function (formId, wadahId) {
  return {
    terpilih: [],
    pilihSemua(ev) {
      const akar = ev.target.closest('[data-bulk-akar]') || document;
      const ids = [...akar.querySelectorAll('[data-pilih]')]
        .filter((el) => {
          if (el.disabled) return false;
          const baris = el.closest('tr, figure, li, div');
          return !baris || baris.style.display !== 'none';
        })
        .map((el) => el.value);
      if (ev.target.checked) this.terpilih = [...new Set([...this.terpilih, ...ids])];
      else this.terpilih = this.terpilih.filter((id) => !ids.includes(id));
    },
    // kirim(pesan) mengirim formulir utama komponen; kirim(pesan, formIdLain, wadahIdLain)
    // mengirim formulir kedua dengan ids[] yang sama (mis. aksi massal non-hapus).
    kirim(pesan, formIdLain, wadahIdLain) {
      const form = document.getElementById(formIdLain || formId);
      const wadah = document.getElementById(wadahIdLain || wadahId);
      if (!form || !wadah) return;
      wadah.innerHTML = '';
      this.terpilih.forEach((id) => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
        wadah.appendChild(inp);
      });
      form.dataset.konfirmasi = pesan.replace('{n}', this.terpilih.length);
      form.requestSubmit();
    },
  };
};
