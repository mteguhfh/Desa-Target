# Kemajuan

Pemulihan kartu di-deploy melalui commit e13bd6e. Beranda, KTP dan peta2d
live 200; pulihkan.php menolak GET dengan 405. tools/ 403, catatan deploy 404.

1.1: panel Aku sekarang memiliki pemilih warna baju, kulit, dan topi. Pilihan
divalidasi server dan disimpan pada kolom warna warga. Uji panel 9/9 lulus.
Commit 7377b37 sudah di-push dan di-deploy. Beranda, KTP, dan peta2d live
membalas 200.

1.2: kartu KTP sekarang menampilkan avatar warga dan panel Aku menampilkan
pratinjau yang berubah saat warna dipilih. Uji panel 11/11 dan lint PHP lulus.

1.7: kode pemulihan, endpoint POST bertoken sesi, formulir KTP dan panel
Aku tersedia. Uji pemulihan 13/13, lint PHP, dan seluruh regresi lulus.
Uji 2D menemukan sesi dibuka setelah output; session_start dipindah ke awal
peta2d.php. Empat uji lama memakai selektor submit global, kini dibatasi
ke formulir penerbitan KTP karena formulir pemulihan juga memiliki submit.
Kode dipakai lewat UPDATE atomik dan mengganti kunci perangkat lama.

Lima uji lulus 44 pemeriksaan. Lint PHP dan enam halaman HTTP lokal lulus.
Seluruh 12 uji resmi sebelumnya lulus, total 539 pemeriksaan.
Lima uji baru lulus 44 pemeriksaan. Total seluruh uji resmi: 583.
Pelaksana awal ikut membaca skrip percobaan dari folder sementara; hasil
skrip di luar tools/uji tidak dipakai sebagai hasil regresi proyek.
Percobaan awal: networkidle lambat saat dua dunia 3D aktif, halaman lama
ditutup; radio gambar diuji lewat label yang memang diketuk pengguna.
Data peta tidak menyediakan nomor pemilik bangunan, pencocokan gambar
menggunakan nama ruko khusus uji. Penandaan punyaku tidak dijadikan syarat
0.3 karena belum didukung payload publik.

Commit f28ffc3 sudah di-push dan di-deploy. Beranda, Tentang, dan Ruko
live membalas 200; tools/ tetap 403 dan DEPLOY.local.md tetap 404.
rg tidak tersedia, memakai grep. Patch pertama ditolak karena header hilang, diperbaiki.
Skill planning digunakan untuk pelacakan; antislop diabaikan sesuai arahan pengguna.
