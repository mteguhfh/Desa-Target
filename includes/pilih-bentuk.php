<label for="<?= e($idBentuk) ?>">Bentuk topi atau rambut</label>
<select id="<?= e($idBentuk) ?>" name="bentuk_topi" class="pilih-bentuk">
    <?php foreach (BENTUK_TOPI as $kode => $nama): ?>
        <option value="<?= e($kode) ?>"><?= e($nama) ?></option>
    <?php endforeach; ?>
</select>
<script>
(function () {
    const pilih = document.getElementById(<?= json_encode($idBentuk) ?>);
    pilih.addEventListener('change', function () {
        (pilih.closest('form') || pilih.parentElement).querySelectorAll('.avatar-warga').forEach(function (avatar) {
            avatar.classList.remove('bentuk-pet', 'bentuk-peci', 'bentuk-caping', 'bentuk-rambut');
            avatar.classList.add('bentuk-' + pilih.value);
        });
    });
})();
</script>
