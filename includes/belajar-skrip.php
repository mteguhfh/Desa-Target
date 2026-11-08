<script>
(() => {
    const akar = document.querySelector('[data-isi="belajar"]');
    if (!akar || akar.dataset.terpasang) return;
    akar.dataset.terpasang = '1';
    const status = akar.querySelector('[data-belajar-panel-status]');
    const wadahKelas = akar.querySelector('[data-belajar-panel-kelas]');
    const wadahMateri = akar.querySelector('[data-belajar-panel-materi]');
    const teks = (s) => String(s ?? '');
    const buat = (tag, kelasNama, isi) => {
        const el = document.createElement(tag);
        if (kelasNama) el.className = kelasNama;
        if (isi !== undefined) el.textContent = teks(isi);
        return el;
    };
    const identitas = () => {
        try {
            const nomor = localStorage.getItem('dt_nomor') || '';
            const kunci = localStorage.getItem('dt_kunci') || '';
            return nomor && kunci ? { nomor, kunci } : null;
        } catch (_) { return null; }
    };
    const ambil = async (url, opsi) => {
        const res = await fetch(url, opsi);
        return res.json();
    };
    const alamatKelas = (slug) => {
        const id = identitas();
        const p = new URLSearchParams({ format: 'json', kelas: slug });
        if (id) { p.set('nomor', id.nomor); p.set('kunci', id.kunci); }
        return 'belajar.php?' + p.toString();
    };
    async function muatKelas() {
        status.textContent = 'Memuat kelas...';
        wadahMateri.replaceChildren();
        try {
            const id = identitas();
            const p = new URLSearchParams({ format: 'json' });
            if (id) { p.set('nomor', id.nomor); p.set('kunci', id.kunci); }
            const data = await ambil('belajar.php?' + p.toString());
            if (!data.ok) throw new Error('gagal');
            status.textContent = data.pengunjung
                ? 'Kamu melihat sebagai pengunjung. Bikin KTP atau masuk dari panel Aku supaya progres dan poin tersimpan.'
                : 'Halo, ' + teks(data.warga?.nama) + '. Pilih kelas untuk melanjutkan belajarmu.';
            wadahKelas.replaceChildren();
            for (const k of data.kelas || []) {
                const kartu = buat('article', 'panel-kartu');
                kartu.append(buat('h3', '', k.judul), buat('p', '', k.keterangan));
                kartu.append(buat('p', '', k.jumlahMateri + ' materi, total ' + k.totalPoin + ' poin. Selesai ' + k.selesai + ' dari ' + k.total + ' (' + k.persen + '%).' + (k.tuntas ? ' Kelas tuntas.' : '')));
                const tombol = buat('button', 'panel-tombol', 'Buka kelas');
                tombol.type = 'button';
                tombol.addEventListener('click', () => muatMateri(k.slug));
                kartu.append(tombol);
                wadahKelas.append(kartu);
            }
        } catch (_) {
            status.textContent = 'Kelas belum bisa dimuat. Buka halaman Belajar penuh dari tautan di bawah.';
        }
    }
    async function kirimTindakan(materiId, ekstra) {
        const id = identitas();
        if (!id) { status.textContent = 'Masuk dulu dari panel Aku supaya penyelesaianmu tercatat.'; return null; }
        const body = new URLSearchParams({ nomor: id.nomor, kunci: id.kunci, materi: String(materiId), ...ekstra });
        try {
            const data = await ambil('belajar.php', { method: 'POST', body });
            status.textContent = data.pesan || 'Selesai.';
            return data;
        } catch (_) {
            status.textContent = 'Koneksi bermasalah. Coba lagi.';
            return null;
        }
    }
    async function muatMateri(slug) {
        status.textContent = 'Memuat materi...';
        try {
            const data = await ambil(alamatKelas(slug));
            if (!data.ok) throw new Error('gagal');
            const kelas = data.kelas;
            wadahKelas.replaceChildren();
            const kembali = buat('button', 'panel-tombol', 'Kembali ke daftar kelas');
            kembali.type = 'button';
            kembali.addEventListener('click', muatKelas);
            wadahKelas.append(kembali, buat('h3', '', kelas.judul));
            wadahKelas.append(buat('p', '', 'Progres: ' + kelas.selesai + ' dari ' + kelas.total + ' selesai (' + kelas.persen + '%).'));
            wadahMateri.replaceChildren();
            for (const m of kelas.materi) {
                const kartu = buat('article', 'panel-kartu');
                const label = m.selesai ? 'Selesai' : (!m.terbuka ? 'Terkunci' : (m.segera ? 'Segera hadir' : 'Terbuka'));
                kartu.append(buat('h4', '', m.judul), buat('p', '', teks(m.tipe) + ', ' + m.poin + ' poin. Status: ' + label + '.'));
                if (m.terbuka && !m.selesai && m.tipe === 'artikel' && m.isi !== undefined) {
                    const isi = buat('div', 'isi-materi');
                    isi.innerHTML = m.isi;
                    kartu.append(isi);
                }
                if (m.terbuka && !m.selesai && m.tipe === 'video') {
                    if (m.segera) {
                        kartu.append(buat('p', 'petunjuk', 'Video masih disiapkan, belum bisa ditandai selesai.'));
                    } else if (m.youtubeId) {
                        const bungkus = buat('div', 'video-bungkus');
                        const iframe = document.createElement('iframe');
                        iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(m.youtubeId);
                        iframe.title = m.judul;
                        iframe.loading = 'lazy';
                        iframe.allowFullscreen = true;
                        bungkus.append(iframe);
                        kartu.append(bungkus);
                    }
                }
                if (m.terbuka && !m.selesai && m.tipe === 'kuis' && Array.isArray(m.soal)) {
                    const form = buat('form', '');
                    m.soal.forEach((s, i) => {
                        const fs = buat('fieldset', 'kolom');
                        fs.append(buat('legend', '', (i + 1) + '. ' + s.tanya));
                        (s.pilihan || []).forEach((pil, j) => {
                            const labelEl = buat('label', 'radio-baris');
                            const radio = document.createElement('input');
                            radio.type = 'radio';
                            radio.name = 'jawaban[' + i + ']';
                            radio.value = String(j);
                            radio.required = true;
                            labelEl.append(radio, document.createTextNode(' ' + teks(pil)));
                            fs.append(labelEl);
                        });
                        form.append(fs);
                    });
                    const kirim = buat('button', 'panel-tombol', 'Kirim jawaban');
                    kirim.type = 'submit';
                    form.append(kirim);
                    form.addEventListener('submit', async (ev) => {
                        ev.preventDefault();
                        const ekstra = { aksi: 'kuis' };
                        new FormData(form).forEach((nilai, nama) => {
                            const cocok = /^jawaban\[(\d+)\]$/.exec(nama);
                            if (cocok) ekstra['jawaban[' + cocok[1] + ']'] = String(nilai);
                        });
                        const hasil = await kirimTindakan(m.id, ekstra);
                        if (hasil && hasil.ok) await muatMateri(slug);
                    });
                    kartu.append(form);
                }
                if (m.terbuka && !m.selesai && m.tipe !== 'kuis' && !m.segera) {
                    const selesaiBtn = buat('button', 'panel-tombol', 'Tandai selesai');
                    selesaiBtn.type = 'button';
                    selesaiBtn.addEventListener('click', async () => {
                        const hasil = await kirimTindakan(m.id, { aksi: 'selesai' });
                        if (hasil && hasil.ok) await muatMateri(slug);
                    });
                    kartu.append(selesaiBtn);
                }
                wadahMateri.append(kartu);
            }
        } catch (_) {
            status.textContent = 'Materi belum bisa dimuat. Buka halaman Belajar penuh dari tautan di bawah.';
        }
    }
    let pernahMuat = false;
    const pengamat = new MutationObserver(() => {
        if (!akar.hidden && !pernahMuat) {
            pernahMuat = true;
            muatKelas();
        }
    });
    pengamat.observe(akar, { attributes: true, attributeFilter: ['hidden'] });
})();
</script>
