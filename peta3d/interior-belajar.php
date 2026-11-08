<?php
// Lapisan Pusat Pembelajaran di dalam peta 3D, mengikuti pola
// interior-balai.php: markup saja, datanya diambil dari endpoint yang sama
// dengan halaman Belajar penuh (belajar.php?format=json, ADR-0004).
// Dibuka dari popover bangunan Pusat Pembelajaran; isinya dirender oleh
// skrip kecil di bawah ini, bukan mesin Three.js ruangan Balai Desa.
?>
<div class="interior-3d interior-belajar" id="interiorBelajar3d" role="dialog" aria-modal="true" aria-labelledby="judulInteriorBelajar" hidden>
    <div class="interior-belajar-kepala">
        <div>
            <h2 id="judulInteriorBelajar">Pusat Pembelajaran</h2>
            <p class="interior-3d-petunjuk" id="interiorBelajarPetunjuk">Kelas untuk warga: dokumen usaha, konten jualan, dan keuangan. Materi berurutan, setiap penyelesaian memberi poin.</p>
        </div>
        <button type="button" class="tombol-keluar-interior" id="tombolKeluarBelajar3d">&larr; Keluar ke Peta 3D</button>
    </div>
    <p class="petunjuk" data-belajar-interior-status>Memuat kelas...</p>
    <div data-belajar-interior-isi></div>
    <p class="petunjuk">Panel ini memakai data yang sama dengan <a href="belajar.php">halaman Belajar penuh</a>.</p>
</div>
<script>
(() => {
    const lapisan = document.getElementById('interiorBelajar3d');
    if (!lapisan) return;
    const status = lapisan.querySelector('[data-belajar-interior-status]');
    const wadah = lapisan.querySelector('[data-belajar-interior-isi]');
    const teks = (s) => String(s ?? '');
    const buat = (tag, isi) => {
        const el = document.createElement(tag);
        if (isi !== undefined) el.textContent = teks(isi);
        return el;
    };
    let sudahMuat = false;
    async function muat() {
        if (sudahMuat) return;
        sudahMuat = true;
        try {
            const p = new URLSearchParams({ format: 'json' });
            try {
                const nomor = localStorage.getItem('dt_nomor') || '';
                const kunci = localStorage.getItem('dt_kunci') || '';
                if (nomor && kunci) { p.set('nomor', nomor); p.set('kunci', kunci); }
            } catch (_) {}
            const res = await fetch('belajar.php?' + p.toString());
            const data = await res.json();
            if (!data.ok) throw new Error('gagal');
            status.textContent = data.pengunjung
                ? 'Kamu melihat sebagai pengunjung. Progres dan poin tersimpan setelah kamu punya KTP Warga dan masuk dari panel Aku.'
                : 'Halo, ' + teks(data.warga?.nama) + '. Lanjutkan kelasmu di halaman Belajar penuh.';
            wadah.replaceChildren();
            for (const k of data.kelas || []) {
                const kartu = buat('article');
                kartu.append(buat('h3', k.judul), buat('p', k.keterangan));
                kartu.append(buat('p', k.jumlahMateri + ' materi, total ' + k.totalPoin + ' poin. Selesai ' + k.selesai + ' dari ' + k.total + ' (' + k.persen + '%).'));
                const tautan = buat('a', 'Buka kelas ini');
                tautan.href = 'belajar.php?kelas=' + encodeURIComponent(k.slug);
                kartu.append(tautan);
                wadah.append(kartu);
            }
        } catch (_) {
            status.textContent = 'Kelas belum bisa dimuat di sini. Buka halaman Belajar penuh dari tautan di bawah.';
        }
    }
    window.__belajar3d = {
        buka: () => { lapisan.hidden = false; muat(); document.getElementById('tombolKeluarBelajar3d')?.focus(); },
        tutup: () => { lapisan.hidden = true; },
    };
    document.getElementById('tombolKeluarBelajar3d')?.addEventListener('click', () => window.__belajar3d.tutup());
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !lapisan.hidden) window.__belajar3d.tutup();
    });
})();
</script>
