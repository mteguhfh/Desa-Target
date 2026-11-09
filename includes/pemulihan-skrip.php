<script>
(function () {
    var form = document.getElementById('formPulihkan');
    if (!form) return;
    var kabar = document.getElementById('pulihKabar');
    var output = document.getElementById('kodePulihBaru');
    var buat = document.getElementById('buatKodePulih');
    function perbarui() {
        buat.hidden = !localStorage.getItem('dt_kunci');
        form.hidden = false;
    }
    window.tampilkanKodePulih = function (kode) {
        output.textContent = 'Nomor: ' + localStorage.getItem('dt_nomor') + ' | Kode: ' + kode;
        output.hidden = false;
        perbarui();
    };
    async function kirim(data, tombol) {
        tombol.disabled = true;
        kabar.textContent = '';
        try {
            data.set('token', window.__tokenKtp);
            var response = await fetch(<?= json_encode(($pemulihanAwalan ?? '') . 'pulihkan') ?>, { method: 'POST', body: data });
            var hasil = await response.json();
            if (!response.ok) { kabar.textContent = hasil.galat; return; }
            if (hasil.kode) {
                window.tampilkanKodePulih(hasil.kode);
                kabar.textContent = 'Simpan kode baru ini. Kode sebelumnya sudah hangus.';
            } else {
                localStorage.setItem('dt_nomor', hasil.nomor);
                localStorage.setItem('dt_kunci', hasil.kunci);
                window.location.reload();
            }
        } catch (e) { kabar.textContent = 'Pemulihan belum berhasil. Coba lagi sebentar.'; }
        finally { tombol.disabled = false; }
    }
    form.addEventListener('submit', function (e) { e.preventDefault(); kirim(new FormData(form), form.querySelector('button')); });
    buat.addEventListener('click', function () {
        var data = new FormData();
        data.set('aksi', 'buat');
        data.set('nomor', localStorage.getItem('dt_nomor') || '');
        data.set('kunci', localStorage.getItem('dt_kunci') || '');
        kirim(data, buat);
    });
    perbarui();
})();
</script>
