(() => {
    const tombol = document.getElementById('suaraDesa');
    if (!tombol) return;
    let konteks, volume, angin, pewaktu, musikTimer, aktif = true, sibuk = false;
    let ketukan = 0;
    const melodi = [261.63, 329.63, 392, 329.63, 293.66, 349.23, 440, 349.23,
        329.63, 392, 523.25, 392, 293.66, 349.23, 392, 293.66];
    function musik() {
        if (!aktif || document.hidden || konteks.state !== 'running') return;
        const mulai = konteks.currentTime;
        const nada = konteks.createOscillator();
        const amplop = konteks.createGain();
        nada.type = 'sine';
        nada.frequency.value = melodi[ketukan++ % melodi.length];
        amplop.gain.setValueAtTime(0, mulai);
        amplop.gain.linearRampToValueAtTime(0.055, mulai + 0.03);
        amplop.gain.exponentialRampToValueAtTime(0.0001, mulai + 1.5);
        nada.connect(amplop).connect(volume);
        nada.onended = () => { nada.disconnect(); amplop.disconnect(); };
        nada.start(mulai); nada.stop(mulai + 1.6);
        musikTimer = setTimeout(musik, 750);
    }

    function siulan() {
        if (!aktif || document.hidden || konteks.state !== 'running') return;
        const mulai = konteks.currentTime;
        const nada = konteks.createOscillator();
        const amplitudo = konteks.createGain();
        const dasar = 1900 + Math.random() * 1000;
        nada.frequency.setValueAtTime(dasar, mulai);
        nada.frequency.exponentialRampToValueAtTime(dasar * 1.35, mulai + 0.09);
        nada.frequency.exponentialRampToValueAtTime(dasar * 0.85, mulai + 0.25);
        amplitudo.gain.setValueAtTime(0, mulai);
        amplitudo.gain.linearRampToValueAtTime(0.035, mulai + 0.025);
        amplitudo.gain.linearRampToValueAtTime(0, mulai + 0.3);
        nada.connect(amplitudo).connect(volume);
        nada.onended = () => { nada.disconnect(); amplitudo.disconnect(); };
        nada.start(mulai); nada.stop(mulai + 0.32);
        pewaktu = setTimeout(siulan, 2500 + Math.random() * 5000);
    }

    function siapkan() {
        const Audio = window.AudioContext || window.webkitAudioContext;
        if (!Audio) throw new Error('Audio tidak tersedia');
        konteks = new Audio();
        volume = konteks.createGain();
        volume.gain.value = 0;
        volume.connect(konteks.destination);
        const buffer = konteks.createBuffer(1, konteks.sampleRate * 4, konteks.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < data.length; i++) data[i] = Math.random() * 2 - 1;
        angin = konteks.createBufferSource();
        angin.buffer = buffer; angin.loop = true;
        const filter = konteks.createBiquadFilter();
        filter.type = 'lowpass'; filter.frequency.value = 380;
        const lembut = konteks.createGain(); lembut.gain.value = 0.09;
        angin.connect(filter).connect(lembut).connect(volume);
        angin.start();
    }

    async function perbarui() {
        clearTimeout(pewaktu);
        clearTimeout(musikTimer);
        if (!konteks) return;
        if (aktif && !document.hidden) {
            await konteks.resume();
            if (!aktif || document.hidden) return;
            clearTimeout(pewaktu);
            clearTimeout(musikTimer);
            volume.gain.setTargetAtTime(0.4, konteks.currentTime, 0.2);
            siulan();
            musik();
        } else {
            volume.gain.cancelScheduledValues(konteks.currentTime);
            volume.gain.value = 0;
            await konteks.suspend();
        }
    }

    function label() {
        tombol.textContent = aktif ? 'Suara: nyala' : 'Suara: mati';
        tombol.setAttribute('aria-pressed', String(aktif));
        tombol.title = aktif ? 'Matikan suara desa' : 'Nyalakan burung, angin, dan musik instrumental';
    }
    tombol.addEventListener('click', async () => {
        if (sibuk) return;
        sibuk = true;
        try {
            if (!konteks) siapkan();
            aktif = !aktif;
            await perbarui();
            label();
        } catch (e) {
            aktif = false; label();
            tombol.textContent = 'Suara tidak tersedia';
        } finally { sibuk = false; }
    });
    document.addEventListener('visibilitychange', () => {
        perbarui().catch(() => { aktif = false; label(); });
    });
    function mulaiDariInteraksi(e) {
        if (e.target.closest?.('#suaraDesa') || !aktif || konteks?.state === 'running') return;
        perbarui().catch(() => { aktif = false; label(); });
    }
    document.addEventListener('pointerdown', mulaiDariInteraksi);
    document.addEventListener('keydown', mulaiDariInteraksi);
    try {
        siapkan();
        label();
        perbarui().catch(() => { aktif = false; label(); });
    } catch (e) {
        aktif = false; label(); tombol.textContent = 'Suara tidak tersedia';
    }
})();
