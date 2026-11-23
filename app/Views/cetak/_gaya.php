<style>
  @page { size: A4; margin: 18mm 16mm; }
  body { font-family: "Times New Roman", serif; font-size: 12pt; color: #000; margin: 0; }
  .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 18px; }
  .kop .sekolah { font-size: 16pt; font-weight: bold; }
  .kop .kecil { font-size: 10pt; }
  h1 { font-size: 13pt; text-align: center; margin: 0 0 2px; text-transform: uppercase; }
  .nomor { text-align: center; margin-bottom: 16px; }
  table { width: 100%; border-collapse: collapse; margin: 10px 0; }
  table.baris th, table.baris td { border: 1px solid #000; padding: 4px 7px; font-size: 11pt; }
  td.angka, th.angka { text-align: right; }
  .ttd { display: flex; justify-content: space-between; margin-top: 34px; gap: 30px; }
  .ttd .kol { width: 45%; }
  .ttd .tengah { text-align: center; }
  .ruang { height: 70px; }
  p { text-align: justify; line-height: 1.45; }
  .takcetak { text-align: center; margin-bottom: 14px; }
  @media print { .takcetak { display: none; } }
  @media screen {
    html { background: #dcdcdc; }
    body { max-width: 186mm; margin: 18px auto; padding: 16mm 14mm; background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,.25); }
  }
</style>
<div class="takcetak"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
