<script>
(function () {
    const game = document.getElementById('panenGame');
    if (!game) return;
    const tombol = document.getElementById('tombolPanen');
    const padi = document.getElementById('panenPadi');
    const kabar = document.getElementById('panenKabar');
    let token = '', mulai = 0, jalan = false;
    const hematGerak = matchMedia('(prefers-reduced-motion: reduce)').matches;
    function pesan(teks) { kabar.hidden = false; kabar.textContent = teks; }
    async function kirim(aksi, waktuKetuk = '') {
        const body = new URLSearchParams({aksi, token, waktu_ketuk: waktuKetuk, nomor: localStorage.getItem('dt_nomor') || '', kunci: localStorage.getItem('dt_kunci') || ''});
        const r = await fetch(game.dataset.endpoint, {method: 'POST', body});
        const data = await r.json();
        if (!r.ok || data.galat) throw new Error(data.galat || 'Panen gagal disimpan.');
        return data;
    }
    function gerak() {
        if (!jalan) return;
        const posisi = 8 + (Math.sin((performance.now() - mulai) / 900 * Math.PI * 2) + 1) * 42;
        if (hematGerak) { padi.style.left = '50%'; padi.textContent = Math.abs(posisi - 50) < 13 ? 'PANEN' : 'TUNGGU'; }
        else padi.style.left = posisi + '%';
        if (performance.now() - mulai > 59000) { jalan = false; tombol.textContent = 'Panen sekarang'; pesan('Waktu habis. Mulai lagi.'); return; }
        requestAnimationFrame(gerak);
    }
    tombol.addEventListener('click', async function () {
        tombol.disabled = true;
        try {
            if (!jalan) {
                pesan('Menyiapkan ladang…');
                const data = await kirim('mulai');
                token = data.token; mulai = performance.now(); jalan = true;
                tombol.textContent = 'Panen di tengah'; pesan('Tekan Enter atau ketuk saat padi di tengah.'); gerak();
            } else {
                jalan = false;
                const data = await kirim('selesai', Math.round(performance.now() - mulai));
                pesan(data.sudah ? 'Hadiah panen hari ini sudah diambil.' : data.ok ? 'Skor ' + data.skor + ', hadiah ' + data.hadiah + ' poin.' : 'Panen belum tersimpan. Coba lagi.');
                if (typeof data.saldo === 'number' && window.__dompetPoin) window.__dompetPoin.tampilSaldo(data.saldo);
                tombol.textContent = 'Panen sekarang';
            }
        } catch (e) { jalan = false; tombol.textContent = 'Panen sekarang'; pesan(e.message); }
        finally { tombol.disabled = false; }
    });
})();
</script>
