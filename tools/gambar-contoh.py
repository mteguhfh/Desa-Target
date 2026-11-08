"""Buat gambar contoh ruko dan lapak dalam gaya flat yang sama dengan desa.

Dipakai sekali untuk membuat berkas di assets/img/. Jalankan lagi hanya kalau
warnanya perlu disesuaikan:

    python3 tools/gambar-contoh.py

Hasilnya PNG 640x480 di assets/img/: contoh-warung, contoh-servis,
contoh-rajut, contoh-kue, dan contoh-es.
"""
from PIL import Image, ImageDraw

LEBAR, TINGGI = 640, 480
SKALA = 2  # gambar dua kali lalu diperkecil, tepinya jadi halus

LANGIT = (214, 240, 247)
LANGIT_BAWAH = (232, 247, 236)
BUKIT = (134, 192, 106)
BUKIT_GELAP = (92, 158, 88)
RIMBUN = (63, 130, 75)
RIMBUN_TERANG = (86, 158, 92)
TANAH = (214, 200, 166)
JALAN = (226, 214, 186)


def latar(d, rumbai=True):
    """Latar desa: langit bergradasi, bukit, pohon, tanah."""
    for y in range(TINGGI * SKALA):
        t = y / (TINGGI * SKALA)
        r = round(LANGIT[0] + (LANGIT_BAWAH[0] - LANGIT[0]) * t)
        g = round(LANGIT[1] + (LANGIT_BAWAH[1] - LANGIT[1]) * t)
        b = round(LANGIT[2] + (LANGIT_BAWAH[2] - LANGIT[2]) * t)
        d.line([(0, y), (LEBAR * SKALA, y)], fill=(r, g, b))

    # Awan sederhana.
    for (cx, cy, s) in [(110, 92, 1.0), (430, 70, 0.8), (545, 130, 0.6)]:
        d.ellipse([(cx - 58 * s, cy - 18 * s), (cx + 58 * s, cy + 22 * s)], fill=(255, 255, 255))
        d.ellipse([(cx - 34 * s, cy - 30 * s), (cx + 20 * s, cy + 14 * s)], fill=(255, 255, 255))

    # Bukit belakang.
    d.ellipse([(-180, 300), (330, 470)], fill=BUKIT)
    d.ellipse([(300, 290), (860, 470)], fill=BUKIT)
    # Pohon di belakang.
    for (x, y, s) in [(70, 300, 1.0), (160, 316, 0.8), (520, 300, 0.9), (600, 322, 0.7)]:
        d.rectangle([(x - 5 * s, y), (x + 5 * s, y + 34 * s)], fill=(120, 88, 60))
        d.ellipse([(x - 34 * s, y - 46 * s), (x + 34 * s, y + 18 * s)], fill=RIMBUN)
        d.ellipse([(x - 16 * s, y - 54 * s), (x + 22 * s, y - 12 * s)], fill=RIMBUN_TERANG)
    # Tanah dan jalan.
    d.rectangle([(0, 388), (LEBAR, TINGGI)], fill=TANAH)
    d.ellipse([(-140, 352), (300, 440)], fill=JALAN)
    d.ellipse([(360, 352), (800, 440)], fill=JALAN)
    if rumbai:
        d.ellipse([(0, 470), (LEBAR, 520)], fill=BUKIT_GELAP)


def kotak(d, xy, warna, tepi=None, lebar_tepi=0, radius=10):
    x0, y0, x1, y1 = [v * SKALA for v in xy]
    if radius and tepi:
        d.rounded_rectangle([x0, y0, x1, y1], radius=radius * SKALA, fill=warna,
                            outline=tepi, width=lebar_tepi * SKALA)
    elif radius:
        d.rounded_rectangle([x0, y0, x1, y1], radius=radius * SKALA, fill=warna)
    elif tepi:
        d.rectangle([x0, y0, x1, y1], fill=warna, outline=tepi, width=lebar_tepi * SKALA)
    else:
        d.rectangle([x0, y0, x1, y1], fill=warna)


def elips(d, xy, warna, tepi=None, lebar_tepi=0):
    x0, y0, x1, y1 = [v * SKALA for v in xy]
    d.ellipse([x0, y0, x1, y1], fill=warna, outline=tepi,
              width=lebar_tepi * SKALA if tepi else 0)


def garis(d, titik, warna, lebar=4):
    d.line([(x * SKALA, y * SKALA) for (x, y) in titik], fill=warna,
           width=int(lebar * SKALA), joint="curve")


def tenda_strip(d, xy, warna_a, warna_b, jumlah=6):
    """Kanopi bergaris miring, ciri khas warung desa."""
    x0, y0, x1, y1 = xy
    lebar = (x1 - x0) / jumlah
    for i in range(jumlah):
        kotak(d, (x0 + i * lebar, y0, x0 + (i + 1) * lebar, y1),
              warna_a if i % 2 == 0 else warna_b, radius=0)


def simpan(nama):
    # Digambar dua kali lalu diperkecil supaya tepinya halus, tapi berkasnya
    # tetap {LEBAR}x{TINGGI} piksel logis.
    kecil = img.resize((LEBAR, TINGGI), Image.LANCZOS)
    kecil.save(f"assets/img/{nama}.png", optimize=True)
    print("assets/img/" + nama + ".png")


# ---------------------------------------------------------------------------
# Warung makan
# ---------------------------------------------------------------------------
img = Image.new("RGB", (LEBAR * SKALA, TINGGI * SKALA), LANGIT)
d = ImageDraw.Draw(img)
latar(d)

# Bangunan warung.
kotak(d, (150, 210, 470, 400), (250, 236, 205), tepi=(176, 150, 112), lebar_tepi=4, radius=8)
tenda_strip(d, (128, 236, 492, 292), (226, 62, 62), (255, 255, 255), jumlah=8)
kotak(d, (128, 288, 492, 300), (196, 46, 46), radius=4)
# Papan nama.
kotak(d, (196, 158, 424, 208), (255, 226, 142), tepi=(160, 118, 44), lebar_tepi=4, radius=10)
garis(d, [(232, 184), (388, 184)], (160, 118, 44), 6)
garis(d, [(232, 196), (340, 196)], (196, 156, 72), 4)
# Etalase.
kotak(d, (180, 312, 440, 348), (255, 255, 255), tepi=(176, 150, 112), lebar_tepi=3, radius=6)
for x in (210, 262, 314, 366, 406):
    elips(d, (x - 14, 318, x + 14, 340), (246, 196, 100) if x % 2 else (226, 128, 92))
# Meja depan.
kotak(d, (150, 360, 470, 400), (170, 122, 78), tepi=(126, 88, 56), lebar_tepi=4, radius=6)
# Kopi dan termos.
kotak(d, (206, 326, 234, 360), (236, 236, 240), tepi=(150, 156, 168), lebar_tepi=3, radius=6)
garis(d, [(214, 318), (212, 306)], (200, 200, 206), 3)
garis(d, [(228, 318), (232, 306)], (200, 200, 206), 3)
elips(d, (392, 330, 424, 352), (222, 84, 64))
kotak(d, (398, 344, 418, 360), (176, 56, 42), radius=3)
# Toples sambal.
elips(d, (320, 328, 350, 356), (220, 70, 48))
kotak(d, (324, 324, 346, 330), (240, 200, 120), radius=2)

simpan("contoh-warung")

# ---------------------------------------------------------------------------
# Servis motor
# ---------------------------------------------------------------------------
img = Image.new("RGB", (LEBAR * SKALA, TINGGI * SKALA), LANGIT)
d = ImageDraw.Draw(img)
latar(d)

kotak(d, (138, 176, 502, 396), (196, 206, 214), tepi=(140, 150, 160), lebar_tepi=4, radius=8)
# Atap seng.
kotak(d, (120, 156, 520, 190), (128, 140, 152), tepi=(96, 106, 118), lebar_tepi=4, radius=6)
for x in range(136, 512, 26):
    garis(d, [(x, 160), (x, 186)], (150, 162, 174), 3)
# Mulut garasi.
kotak(d, (170, 210, 470, 396), (92, 104, 116), radius=6)
kotak(d, (180, 220, 460, 396), (124, 136, 148), radius=4)
# Papan nama.
kotak(d, (232, 118, 408, 156), (255, 214, 102), tepi=(160, 118, 44), lebar_tepi=4, radius=8)
garis(d, [(258, 138), (382, 138)], (160, 118, 44), 6)
# Meja kerja dan kotak peralatan.
kotak(d, (196, 300, 420, 330), (168, 122, 78), tepi=(126, 88, 56), lebar_tepi=3, radius=4)
kotak(d, (216, 268, 262, 300), (212, 66, 58), tepi=(150, 46, 40), lebar_tepi=3, radius=4)
garis(d, [(224, 280), (254, 280)], (255, 255, 255), 3)
# Kunci inggris di dinding.
garis(d, [(400, 250), (446, 232)], (196, 202, 210), 6)
elips(d, (438, 222, 456, 240), (196, 202, 210))
# Motor.
elips(d, (216, 344, 268, 392), (60, 64, 72))
elips(d, (364, 344, 416, 392), (60, 64, 72))
elips(d, (232, 360, 252, 380), (180, 186, 194))
elips(d, (380, 360, 400, 380), (180, 186, 194))
garis(d, [(242, 368), (312, 330), (372, 330), (390, 368)], (206, 68, 56), 9)
kotak(d, (300, 312, 356, 336), (226, 52, 46), radius=6)
garis(d, [(316, 330), (316, 352)], (120, 124, 132), 5)
# Tumpukan ban.
for i, y in enumerate((352, 372, 392)):
    elips(d, (438, y, 498, y + 34), (56, 60, 68))
    elips(d, (454, y + 8, 482, y + 26), (150, 156, 164))
# Kaleng oli.
kotak(d, (150, 350, 180, 396), (70, 130, 190), tepi=(46, 96, 150), lebar_tepi=3, radius=4)

simpan("contoh-servis")

# ---------------------------------------------------------------------------
# Rajut tangan
# ---------------------------------------------------------------------------
img = Image.new("RGB", (LEBAR * SKALA, TINGGI * SKALA), LANGIT)
d = ImageDraw.Draw(img)
latar(d)

# Rak kayu.
kotak(d, (140, 200, 500, 400), (228, 210, 184), tepi=(176, 150, 112), lebar_tepi=4, radius=8)
kotak(d, (140, 200, 500, 214), (170, 122, 78), radius=3)
for x in (150, 330, 488):
    kotak(d, (x, 214, x + 12, 400), (170, 122, 78), radius=2)
# Tas rajut bergantung.
for (x, warna_a, warna_b) in [(196, (232, 168, 64), (250, 210, 130)),
                              (262, (206, 104, 128), (238, 158, 176)),
                              (392, (66, 152, 146), (128, 200, 194)),
                              (452, (232, 168, 64), (250, 210, 130))]:
    garis(d, [(x, 214), (x, 248)], (120, 96, 70), 4)
    kotak(d, (x - 30, 248, x + 30, 330), warna_a, tepi=(150, 108, 60), lebar_tepi=3, radius=14)
    garis(d, [(x - 22, 258), (x + 22, 258)], warna_b, 5)
    garis(d, [(x - 24, 276), (x + 24, 276)], warna_b, 5)
    garis(d, [(x - 24, 294), (x + 24, 294)], warna_b, 5)
    garis(d, [(x - 22, 312), (x + 22, 312)], warna_b, 5)
# Meja jahit.
kotak(d, (150, 360, 490, 400), (170, 122, 78), tepi=(126, 88, 56), lebar_tepi=4, radius=6)
# Gulungan benang.
for (x, warna) in [(210, (232, 168, 64)), (250, (206, 104, 128)), (290, (66, 152, 146)), (330, (126, 96, 176))]:
    elips(d, (x - 20, 322, x + 20, 362), warna, tepi=(90, 70, 60), lebar_tepi=3)
    elips(d, (x - 6, 334, x + 6, 346), (250, 244, 232))
# Keranjang bambu.
elips(d, (388, 320, 470, 366), (214, 174, 104), tepi=(150, 112, 56), lebar_tepi=4)
garis(d, [(396, 334), (462, 334)], (150, 112, 56), 3)
garis(d, [(398, 348), (460, 348)], (150, 112, 56), 3)

simpan("contoh-rajut")

# ---------------------------------------------------------------------------
# Kue bolu kukus (lapak tanpa ruko)
# ---------------------------------------------------------------------------
img = Image.new("RGB", (LEBAR * SKALA, TINGGI * SKALA), LANGIT)
d = ImageDraw.Draw(img)
latar(d)

# Tenda lapak.
kotak(d, (188, 262, 452, 300), (255, 255, 255))
tenda_strip(d, (170, 200, 470, 262), (232, 168, 64), (255, 255, 255), jumlah=7)
kotak(d, (170, 256, 470, 268), (196, 132, 40), radius=4)
garis(d, [(196, 262), (196, 400)], (150, 110, 60), 6)
garis(d, [(444, 262), (444, 400)], (150, 110, 60), 6)
# Meja.
kotak(d, (176, 336, 464, 376), (170, 122, 78), tepi=(126, 88, 56), lebar_tepi=4, radius=6)
# Loyang kue.
for (x, y) in [(230, 300), (300, 292), (370, 300), (265, 322), (335, 322)]:
    elips(d, (x - 28, y, x + 28, y + 26), (246, 216, 156), tepi=(196, 156, 96), lebar_tepi=3)
    elips(d, (x - 20, y + 2, x + 20, y + 12), (252, 236, 196))
    elips(d, (x - 6, y + 6, x + 6, y + 16), (206, 96, 120))
# Kukusan di samping.
kotak(d, (404, 300, 456, 340), (222, 190, 140), tepi=(170, 130, 80), lebar_tepi=3, radius=6)
elips(d, (400, 288, 460, 308), (222, 190, 140), tepi=(170, 130, 80), lebar_tepi=3)

simpan("contoh-kue")

# ---------------------------------------------------------------------------
# Es teh jumbo (lapak tanpa ruko)
# ---------------------------------------------------------------------------
img = Image.new("RGB", (LEBAR * SKALA, TINGGI * SKALA), LANGIT)
d = ImageDraw.Draw(img)
latar(d)

kotak(d, (188, 268, 452, 300), (255, 255, 255))
tenda_strip(d, (170, 200, 470, 268), (66, 152, 146), (255, 255, 255), jumlah=7)
kotak(d, (170, 262, 470, 274), (40, 116, 110), radius=4)
garis(d, [(196, 268), (196, 400)], (150, 110, 60), 6)
garis(d, [(444, 268), (444, 400)], (150, 110, 60), 6)
kotak(d, (176, 340, 464, 380), (170, 122, 78), tepi=(126, 88, 56), lebar_tepi=4, radius=6)
# Gelas jumbo.
for (x, y, s) in [(258, 276, 1.0), (330, 286, 0.85), (398, 280, 0.75)]:
    leb = 26 * s
    kotak(d, (x - leb, y, x + leb, y + 78 * s), (250, 214, 150), tepi=(196, 156, 96), lebar_tepi=3, radius=6)
    kotak(d, (x - leb + 4, y + 34 * s, x + leb - 4, y + 74 * s), (168, 104, 56), radius=4)
    garis(d, [(x + 4, y + 10), (x + 14, y + 40 * s)], (255, 255, 255), 5)
    elips(d, (x - 16 * s, y - 12 * s, x + 16 * s, y + 8 * s), (250, 214, 150), tepi=(196, 156, 96), lebar_tepi=3)
    kotak(d, (x + 6, y - 30 * s, x + 12, y - 6 * s), (226, 62, 62), radius=2)
# Termos es.
kotak(d, (420, 302, 462, 344), (60, 130, 190), tepi=(40, 96, 146), lebar_tepi=3, radius=6)

simpan("contoh-es")
