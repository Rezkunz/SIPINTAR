<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 28px; font-size: 13px; }
    .kop { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #0D5C46; padding-bottom: 10px; margin-bottom: 18px; }
    .kop-nama { font-size: 18px; font-weight: 800; color: #0D5C46; }
    .kop-sub { font-size: 12px; color: #4B5563; }
    .kop-dok { text-align: right; }
    .kop-dok .judul { font-size: 15px; font-weight: 800; text-transform: uppercase; }
    .kop-dok .nomor { font-size: 12px; color: #4B5563; }
    .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin-bottom: 18px; }
    .meta div span { display: inline-block; min-width: 130px; color: #4B5563; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    th, td { border: 1px solid #D1D5DB; padding: 7px 8px; font-size: 12px; vertical-align: top; }
    th { background: #F3F4F6; text-align: left; }
    .kanan { text-align: right; white-space: nowrap; }
    .tengah { text-align: center; }
    tfoot td { font-weight: 800; background: #F9FAFB; }
    .ringkas { display: flex; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
    .ringkas div { flex: 1; min-width: 150px; border: 1px solid #D1D5DB; border-radius: 6px; padding: 8px 10px; }
    .ringkas span { display: block; font-size: 11px; color: #4B5563; text-transform: uppercase; }
    .ringkas strong { font-size: 15px; }
    .ttd { display: flex; justify-content: space-between; margin-top: 36px; }
    .ttd > div { width: 40%; text-align: center; }
    .ttd .garis { margin-top: 64px; border-top: 1px solid #111827; padding-top: 4px; font-weight: 700; }
    .batal { border: 2px solid #DC2626; color: #DC2626; font-weight: 800; padding: 6px 10px; display: inline-block; margin-bottom: 12px; }
    .catatan { font-size: 11px; color: #6B7280; margin-top: 8px; }
    .tombol-cetak { margin-bottom: 18px; padding: 8px 16px; cursor: pointer; font-weight: 700; background: #0D5C46; color: #FFFFFF; border: none; border-radius: 6px; }
    @media print { .tombol-cetak { display: none; } body { margin: 10mm; } }
</style>
