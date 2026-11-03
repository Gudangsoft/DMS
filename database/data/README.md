# Paket data instansi

Kode aplikasi tidak memuat identitas instansi mana pun. Nilai awal milik satu instansi diletakkan di
`database/data/instansi/setting.json` (tidak ikut repositori), berupa pasangan `key` → `value` tabel
`settings`, mis. `{"site_tagline": "Nama Kampus", "contact_email": "info@kampus.ac.id"}`.

Teks bawaan halaman, slider, dan subkategori dokumen memakai `{nama_institusi}` (dari `site_tagline`)
dan `{nama_situs}` (dari `site_name`).

Tanpa paket ini seeder memakai isi netral; identitas lalu dilengkapi dari menu **Pengaturan Web**.
Seeder tidak menimpa pengaturan, halaman, maupun slider yang sudah ada. Uji otomatis selalu memakai isi netral.

Impor dari situs WordPress lama (`wp:import-legacy`, `wp:restructure-categories`, dan migrasi perbaikan
data terkait) hanya berjalan bila `LEGACY_WORDPRESS_URL` diisi.
