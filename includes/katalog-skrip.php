<script>
document.querySelectorAll('.ruko-grid, .daftar-ruko, .lapak-grid, .pasar-lapak').forEach(function (daftar, i) {
    const label = document.createElement('label'); label.textContent = 'Kategori usaha';
    const pilih = document.createElement('select'); pilih.id = 'filterKatalog' + i; label.htmlFor = pilih.id;
    [['','Semua kategori'],['makanan','Makanan'],['jasa','Jasa'],['kerajinan','Kerajinan'],['lainnya','Lainnya']].forEach(function (item) { pilih.add(new Option(item[1], item[0])); });
    daftar.before(label, pilih);
    const kosong = document.createElement('p'); kosong.textContent = 'Belum ada usaha di kategori ini.'; kosong.hidden = true; daftar.after(kosong);
    function filter() { let jumlah = 0; Array.from(daftar.children).forEach(function (kartu) { kartu.hidden = !!pilih.value && (kartu.dataset.kategori || 'lainnya') !== pilih.value; if (!kartu.hidden) jumlah++; }); kosong.hidden = jumlah > 0 || !pilih.value; }
    pilih.addEventListener('change', filter);
    new MutationObserver(filter).observe(daftar, {childList:true});
});
</script>
