<section class="pemulihan-kartu" aria-label="Pemulihan kartu">
    <h3>Pemulihan kartu</h3>
    <p>Simpan nomor warga dan kode ini di tempat pribadi. Kode hanya tampil saat dibuat dan berlaku sekali. Memulihkan kartu mengganti kunci perangkat lama.</p>
    <form id="formPulihkan">
        <label for="pulihNomor">Nomor warga</label>
        <input id="pulihNomor" name="nomor" placeholder="DT-0001" required autocomplete="off">
        <label for="pulihKode">Kode pemulihan</label>
        <input id="pulihKode" name="kode" required autocomplete="off" spellcheck="false">
        <button type="submit">Pulihkan kartu</button>
    </form>
    <button type="button" id="buatKodePulih">Buat kode pemulihan baru</button>
    <p id="pulihKabar" role="status"></p>
    <output id="kodePulihBaru" hidden></output>
</section>
