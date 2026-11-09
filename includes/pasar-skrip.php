<?php
// Pasar Malam dan CFD di panel bersayap (Fitur 4).
//
// Aturan yang dijaga berkas ini:
//   - Lapak gratis, tanpa pembayaran dan tanpa potong poin.
//   - Lapak hanya bisa diambil saat acara berlangsung; di luar jam acara
//     panel tetap menampilkan jadwal dan daftar lapak acara berikutnya.
//   - Warga yang sudah punya ruko tidak diminta mengisi nama usaha; server
//     mengambil nama dan tautan dari rukonya. Warga tanpa ruko (atau yang
//     rukonya sudah dilepas) mengisi nama usaha dan tautan di panel.
//   - Hitung mundur memakai jam peramban pengunjung; server tetap yang
//     menentukan acara mana yang sedang berlangsung.
//
// Berkas ini hanya mengeluarkan markup penanda + <script>.
?>
<span id="pasarPenanda" hidden
      data-endpoint="<?= e($awalanPasar ?? '') ?>lapak"
      data-jadwal="<?= e(json_encode(ACARA_LAPAK, JSON_UNESCAPED_UNICODE)) ?>"></span>
<script>
(function () {
    var penanda = document.getElementById('pasarPenanda');
    var panelPasar = document.querySelector('[data-isi="pasar"]');
    if (!penanda || !panelPasar) return;

    var endpoint = penanda.getAttribute('data-endpoint') || 'lapak';
    // Endpoint boleh absolut dari situs induk (mis. saat dipakai peta) atau
    // relatif dari halaman pasar.php, jadi diambil apa adanya.
    var jadwal = {};
    try { jadwal = JSON.parse(penanda.getAttribute('data-jadwal') || '{}'); } catch (e) { jadwal = {}; }
    var kodeAcara = Object.keys(jadwal);
    if (!kodeAcara.length) return;

    var elStatus = document.getElementById('pasarStatus');
    var elPilih = document.getElementById('pasarPilih');
    var elLapak = document.getElementById('pasarLapak');
    var elKlaim = document.getElementById('pasarKlaim');
    var elForm = document.getElementById('pasarForm');
    var elKabar = document.getElementById('pasarKabar');
    var elTamu = document.getElementById('pasarTamu');
    var tombolKlaim = document.getElementById('tombolKlaimLapak');
    var tombolKirim = document.getElementById('tombolKirimLapak');

    var nomor = '';
    var kunci = '';
    try {
        nomor = localStorage.getItem('dt_nomor') || '';
        kunci = localStorage.getItem('dt_kunci') || '';
    } catch (e) { nomor = ''; }
    var punyaKartu = /^DT-\d{4,9}$/.test(nomor) && kunci !== '';

    var acaraDipilih = kodeAcara[0];
    var keadaan = null;
    var pewaktuMundur = null;
    var sudahMuat = false;
    var HARI = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    function potong(teks, batas) {
        teks = teks == null ? '' : String(teks);
        return teks.length > batas ? teks.slice(0, batas - 1) + '…' : teks;
    }

    function tampilKabar(teks, galat) {
        if (!elKabar) return;
        elKabar.textContent = teks || '';
        elKabar.hidden = !teks;
        elKabar.classList.toggle('galat', !!galat);
    }

    function buatPemilih() {
        if (!elPilih) return;
        elPilih.innerHTML = '';
        kodeAcara.forEach(function (kode) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'pasar-pil';
            b.setAttribute('data-acara', kode);
            b.textContent = (jadwal[kode] && jadwal[kode].nama) || kode;
            b.addEventListener('click', function () { gantiAcara(kode); });
            elPilih.appendChild(b);
        });
        tandaiPemilih();
    }

    function tandaiPemilih() {
        if (!elPilih) return;
        Array.prototype.forEach.call(elPilih.querySelectorAll('.pasar-pil'), function (b) {
            var cocok = b.getAttribute('data-acara') === acaraDipilih;
            b.classList.toggle('aktif', cocok);
            b.setAttribute('aria-pressed', cocok ? 'true' : 'false');
        });
    }

    function mulaiMundur() {
        if (!keadaan || !keadaan.berikutnya || !elStatus) return;
        var target = new Date(keadaan.berikutnya.tanggal + 'T' + keadaan.berikutnya.mulai + ':00');
        var tick = function () {
            var depan = elStatus.querySelector('.pasar-mundur');
            if (!depan) return;
            var sisa = Math.floor((target.getTime() - Date.now()) / 1000);
            if (sisa <= 0) { depan.textContent = 'sebentar lagi'; return; }
            var jam = Math.floor(sisa / 3600);
            var menit = Math.floor((sisa % 3600) / 60);
            depan.textContent = jam > 0 ? jam + ' jam ' + menit + ' menit lagi' : menit + ' menit lagi';
        };
        if (pewaktuMundur !== null) window.clearInterval(pewaktuMundur);
        tick();
        pewaktuMundur = window.setInterval(tick, 60000);
    }

    function gambarStatus() {
        if (!elStatus || !keadaan) return;
        elStatus.innerHTML = '';
        var namaAcaraDipilih = (jadwal[acaraDipilih] && jadwal[acaraDipilih].nama) || 'Acara';
        if (keadaan.aktif && keadaan.acara === acaraDipilih) {
            elStatus.textContent = namaAcaraDipilih + ' sedang berlangsung sampai ' +
                ((jadwal[acaraDipilih] && jadwal[acaraDipilih].selesai) || '') + '.';
            elStatus.classList.add('aktif');
            return;
        }
        elStatus.classList.remove('aktif');
        var b = keadaan.berikutnya;
        if (!b) {
            elStatus.textContent = 'Jadwal acara belum tersedia.';
            return;
        }
        var teks = document.createElement('span');
        teks.textContent = b.nama + ' berikutnya ' + b.tanggal + ' pukul ' + b.mulai + ', ';
        var mundur = document.createElement('strong');
        mundur.className = 'pasar-mundur';
        mundur.textContent = '…';
        elStatus.appendChild(teks);
        elStatus.appendChild(mundur);
        mulaiMundur();
    }

    function gambarLapak() {
        if (!elLapak) return;
        elLapak.innerHTML = '';
        var daftar = (keadaan && Array.isArray(keadaan.lapak)) ? keadaan.lapak : [];
        if (!daftar.length) {
            var kosong = document.createElement('li');
            kosong.className = 'pasar-kosong';
            kosong.textContent = 'Belum ada lapak. Jadilah yang pertama buka lapak.';
            elLapak.appendChild(kosong);
        }
        daftar.forEach(function (l) {
            var li = document.createElement('li');
            li.className = 'pasar-baris';
            li.dataset.kategori = l.kategori || 'lainnya';
            var nama = document.createElement('span');
            nama.className = 'pasar-baris-nama';
            nama.textContent = potong(l.namaUsaha, 60);
            var meta = document.createElement('span');
            meta.className = 'pasar-baris-meta';
            meta.textContent = 'Tenda ' + ((Number(l.kavling) || 0) + 1);
            meta.textContent += ' · ' + (l.kategori || 'lainnya') + ' · ' + (l.harga == null ? 'Tanyakan harga' : 'Rp' + Number(l.harga).toLocaleString('id-ID'));
            li.appendChild(nama);
            li.appendChild(meta);
            if (typeof l.gambar === 'string' && l.gambar.indexOf('assets/img/') === 0) {
                var img = document.createElement('img');
                img.className = 'gambar-usaha pasar-baris-gambar';
                img.src = l.gambar;
                img.alt = 'Gambar ' + potong(l.namaUsaha, 60);
                img.loading = 'lazy';
                img.width = 640;
                img.height = 480;
                li.appendChild(img);
            }
            if (typeof l.tautan === 'string' && /^https:\/\//i.test(l.tautan)) {
                var a = document.createElement('a');
                a.className = 'pasar-baris-tautan';
                a.href = l.tautan;
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
                a.textContent = 'Kunjungi lapak';
                li.appendChild(a);
            }
            elLapak.appendChild(li);
        });
    }

    // Tombol ambil lapak hanya di acara yang sedang berlangsung, untuk warga
    // yang punya kartu dan belum mengambil lapak, selama masih ada tempat.
    function gambarTombol() {
        if (!keadaan) return;
        var aktif = keadaan.aktif && keadaan.acara === acaraDipilih;
        var daftar = keadaan.lapak || [];
        var penuh = daftar.length >= keadaan.batas;
        var punyaku = daftar.some(function (l) { return l.nomor === nomor; });
        if (elTamu) elTamu.hidden = punyaKartu || aktif;
        if (elKlaim) elKlaim.hidden = !(aktif && punyaKartu && !penuh && !punyaku);
        if (tombolKlaim) tombolKlaim.disabled = penuh;
        // Formulir ringkas tertutup selama lapak sudah ada atau acara belum
        // berlangsung; kalau server minta nama usaha, formulir dibuka lagi.
        if (elForm && (punyaku || !aktif)) elForm.hidden = true;
        var elGambar = document.getElementById('pasarGambar');
        if (elGambar) elGambar.hidden = !elForm || elForm.hidden;
    }

    function muat() {
        var url = endpoint + '?acara=' + encodeURIComponent(acaraDipilih);
        return fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || data.acara !== acaraDipilih) return;
                keadaan = data;
                gambarStatus();
                gambarLapak();
                gambarTombol();
            })
            .catch(function () {});
    }

    function gantiAcara(kode) {
        if (kode === acaraDipilih) return;
        acaraDipilih = kode;
        keadaan = null;
        if (elLapak) elLapak.innerHTML = '';
        if (elForm) elForm.hidden = true;
        tandaiPemilih();
        tampilKabar('');
        muat();
    }

    function kirimKlaim(namaUsaha, tautan) {
        var body = 'acara=' + encodeURIComponent(acaraDipilih) +
            '&nomor=' + encodeURIComponent(nomor) +
            '&kunci=' + encodeURIComponent(kunci);
        if (namaUsaha) body += '&nama_usaha=' + encodeURIComponent(namaUsaha);
        if (tautan) body += '&tautan=' + encodeURIComponent(tautan);
        body += '&kategori=' + encodeURIComponent(document.getElementById('pasarKategori').value) + '&harga=' + encodeURIComponent(document.getElementById('pasarHarga').value);
        var pilihanGambar = document.querySelector('#pasarGambar input[name="gambar_lapak"]:checked');
        if (pilihanGambar && pilihanGambar.value) body += '&gambar=' + encodeURIComponent(pilihanGambar.value);
        if (tombolKlaim) tombolKlaim.disabled = true;
        if (tombolKirim) tombolKirim.disabled = true;
        return fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (data) {
                return { status: r.status, data: data };
            });
        }).then(function (hasil) {
            if (hasil.status === 201) {
                tampilKabar('Lapakmu siap. Sampai jumpa di acara!');
                if (elForm) elForm.hidden = true;
            } else if (hasil.status === 422) {
                // Server menolak nama usaha atau tautan: warga tanpa ruko
                // perlu mengisi formulir ringkas.
                if (elForm) elForm.hidden = false;
                var elGambarBuka = document.getElementById('pasarGambar');
                if (elGambarBuka) elGambarBuka.hidden = false;
                tampilKabar(hasil.data.galat || 'Isi nama usaha dan tautan jualan dulu.', true);
            } else {
                tampilKabar(hasil.data.galat || 'Lapak belum bisa diambil.', true);
            }
        }).catch(function () {
            tampilKabar('Jaringan sedang rewel. Coba lagi sebentar.', true);
        }).then(function () {
            if (tombolKlaim) tombolKlaim.disabled = false;
            if (tombolKirim) tombolKirim.disabled = false;
            muat();
        });
    }

    if (tombolKlaim) {
        tombolKlaim.addEventListener('click', function () {
            if (!punyaKartu) {
                tampilKabar('Bikin KTP Warga dulu supaya lapak tercatat atas namamu.', true);
                return;
            }
            // Warga dengan ruko: server memakai nama dan tautan rukonya.
            kirimKlaim('', '');
        });
    }

    if (tombolKirim) {
        tombolKirim.addEventListener('click', function () {
            var isianNama = document.getElementById('pasarNamaUsaha');
            var isianTautan = document.getElementById('pasarTautan');
            kirimKlaim(
                isianNama ? isianNama.value.trim() : '',
                isianTautan ? isianTautan.value.trim() : ''
            );
        });
    }

    function saatPanelTampil() {
        if (panelPasar.hidden) return;
        if (!sudahMuat) {
            sudahMuat = true;
            buatPemilih();
        }
        muat();
    }
    if (window.MutationObserver) {
        new MutationObserver(saatPanelTampil)
            .observe(panelPasar, { attributes: true, attributeFilter: ['hidden'] });
    }
    saatPanelTampil();

    // Untuk uji otomatis.
    window.__pasarDesa = {
        acara: function () { return acaraDipilih; },
        keadaan: function () { return keadaan; },
        pilih: gantiAcara
    };
})();
</script>
