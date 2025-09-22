/* ====== DATA & STATE ====== */
const berita = JSON.parse(localStorage.getItem("berita")) || [];
const struktur = [
  { jabatan: "Direktur", nama: "Budi Santoso" },
  { jabatan: "Manajer Operasional", nama: "Sari Wijaya" },
  { jabatan: "Staf IT", nama: "Andi Saputra" }
];
let adminLoggedIn = false;
let editingIndex = null;

/* ====== NAVBAR: hamburger & dropdown ====== */
//const navToggle = document.addEventListener('DOMContentLoaded', () => {
  //const toggleBtn = document.getElementById('navToggle');
  //const navMenu = document.getElementById('navMenu');

  // buka/tutup panel nav di mobile
  //toggleBtn.addEventListener('click', () => {
    //const open = navMenu.classList.toggle('open');
    //toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  //});

  // dropdown logic (klik untuk toggle)
  document.querySelectorAll('.dropdown').forEach(dd => {
    const btn = dd.querySelector('.dropdown-toggle');
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const isOpen = dd.classList.toggle('open');
      dd.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // tutup dropdown bila klik di luar (desktop)
    document.addEventListener('click', (e) => {
      if (!dd.contains(e.target)) {
        dd.classList.remove('open');
        dd.setAttribute('aria-expanded', 'false');
      }
    });

    // hover open untuk desktop (opsional)
    dd.addEventListener('mouseenter', () => {
      if (window.matchMedia('(min-width: 821px)').matches) {
        dd.classList.add('open');
        dd.setAttribute('aria-expanded', 'true');
      }
    });
    dd.addEventListener('mouseleave', () => {
      if (window.matchMedia('(min-width: 821px)').matches) {
        dd.classList.remove('open');
        dd.setAttribute('aria-expanded', 'false');
      }
    });
  });


// helper untuk menutup panel nav setelah pilih menu (mobile)
function closeNav() {
  const navMenu = document.getElementById('navMenu');
  const toggleBtn = document.getElementById('navToggle');
  if (navMenu && navMenu.classList.contains('open')) {
    navMenu.classList.remove('open');
    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
  }
}

/* ====== NAVIGASI HALAMAN ====== */
function showSection(id) {
  document.querySelectorAll("section").forEach(s => s.classList.add("hidden"));
  document.getElementById(id).classList.remove("hidden");

  if (id === "admin") {
    if (adminLoggedIn) {
      document.getElementById("akses-admin").classList.add("hidden");
      document.getElementById("form-admin").classList.remove("hidden");
    } else {
      document.getElementById("akses-admin").classList.remove("hidden");
      document.getElementById("form-admin").classList.add("hidden");
    }
  }
}

/* ====== LOGIN / LOGOUT (DEMO) ====== */
async function prosesLogin() {
  const u = document.getElementById("username").value.trim();
  const p = document.getElementById("password").value.trim();
  const err = document.getElementById("login-error");

  try {
    const res = await fetch('php/login.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ username: u, password: p })
    });
    const data = await res.json();
    if (data.success) {
      adminLoggedIn = true;
      err.textContent = "";
      document.getElementById("login-button").classList.add("hidden");
      document.getElementById("logout-button").classList.remove("hidden");
      document.getElementById("login-title").textContent = "Admin Aktif";
      showSection("admin");
      tampilkanBerita();
      renderHome && renderHome();
    } else {
      err.textContent = data.message || "Gagal login.";
    }
  } catch (e) {
    err.textContent = "Terjadi kesalahan koneksi.";
  }
}

async function logout() {
  try {
    await fetch('php/logout.php', { method: 'POST' });
  } catch(e) {}
  adminLoggedIn = false;
  batalEdit();
  document.getElementById("login-button").classList.remove("hidden");
  document.getElementById("logout-button").classList.add("hidden");
  document.getElementById("login-title").textContent = "Login Admin";
  showSection("home");
  tampilkanBerita();
  renderHome && renderHome();
}

/* ====== CRUD BERITA + NOTIF ====== */
function tambahBerita() {
  const judul = document.getElementById('judul').value;
  const isi = document.getElementById('isi').value;
  const fileInput = document.getElementById('media');
  const file = fileInput.files[0];

  if (!judul.trim() || !isi.trim()) {
    alert('Judul dan isi berita tidak boleh kosong.');
    return;
  }

  // MODE EDIT
  if (editingIndex !== null) {
    const updateItem = (mediaData, type) => {
      const target = berita[editingIndex];
      target.judul = judul;
      target.isi = isi;
      if (mediaData && type) {
        target.media = mediaData;
        target.type = type;
      }
      localStorage.setItem("berita", JSON.stringify(berita));
      tampilkanBerita();
      showNotif("Berita berhasil diperbarui!", "#3b82f6");
      batalEdit();
    };

    if (file) {
      const reader = new FileReader();
      reader.onload = e => updateItem(e.target.result, file.type);
      reader.readAsDataURL(file);
    } else {
      updateItem(null, null);
    }
    return;
  }

  // MODE TAMBAH
  if (file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      const mediaData = e.target.result;
      berita.unshift({ judul, isi, media: mediaData, type: file.type });
      localStorage.setItem("berita", JSON.stringify(berita));
      tampilkanBerita();
      showNotif("Berita berhasil ditambahkan!");
    };
    reader.readAsDataURL(file);
  } else {
    berita.unshift({ judul, isi });
    localStorage.setItem("berita", JSON.stringify(berita));
    tampilkanBerita();
    showNotif("Berita berhasil ditambahkan!");
  }

  document.getElementById('judul').value = '';
  document.getElementById('isi').value = '';
  fileInput.value = '';
}

function hapusBerita(index) {
  if (!adminLoggedIn) return;
  if (!confirm("Hapus berita ini?")) return;
  berita.splice(index, 1);
  localStorage.setItem("berita", JSON.stringify(berita));
  tampilkanBerita();
  if (editingIndex === index) batalEdit();
}

function mulaiEdit(index) {
  if (!adminLoggedIn) return;

  showSection('admin');
  document.getElementById('form-admin').classList.remove('hidden');
  document.getElementById('akses-admin').classList.add('hidden');

  const item = berita[index];
  document.getElementById('judul').value = item.judul || '';
  document.getElementById('isi').value = item.isi || '';
  document.getElementById('media').value = '';

  editingIndex = index;
  document.getElementById('submit-berita').innerText = 'Simpan Perubahan';
  document.getElementById('cancel-edit').classList.remove('hidden');
  document.getElementById('admin-hint').textContent = "Sedang mengedit berita. Ganti judul/isi, dan opsional ganti media.";
}

function batalEdit() {
  editingIndex = null;
  document.getElementById('submit-berita').innerText = 'Tambah Berita';
  document.getElementById('cancel-edit').classList.add('hidden');
  document.getElementById('admin-hint').textContent = "Saat mode edit aktif, tombol di atas berubah menjadi “Simpan Perubahan”.";
  document.getElementById('judul').value = '';
  document.getElementById('isi').value = '';
  document.getElementById('media').value = '';
}

/* ====== Render ====== */
function tampilkanBerita() {
  const container = document.getElementById('daftar-berita');
  container.innerHTML = berita.map((b, i) => {
    let mediaHTML = '';
    if (b.media) {
      if (b.type && b.type.startsWith('image')) {
        mediaHTML = `<img src="${b.media}" alt="">`;
      } else if (b.type && b.type.startsWith('video')) {
        mediaHTML = `<video controls><source src="${b.media}" type="${b.type}"></video>`;
      }
    }
    const tombolAdmin = adminLoggedIn
      ? `<div class="toolbar">
           <button onclick="mulaiEdit(${i})">Edit</button>
           <button class="warn" onclick="hapusBerita(${i})">Hapus</button>
         </div>`
      : '';

    return `
      <div class="card">
        <strong>${b.judul}</strong>
        <p>${b.isi}</p>
        ${mediaHTML}
        ${tombolAdmin}
      </div>`;
  }).join('');
}

function tampilkanStruktur() {
  const el = document.getElementById('daftar-struktur');
  el.innerHTML = struktur.map(s => `
    <div class="card"><strong>${s.jabatan}:</strong> ${s.nama}</div>
  `).join('');
}

/* ====== Notifikasi ====== */
function showNotif(pesan, warna = "#22c55e") {
  const notif = document.getElementById("notif");
  notif.textContent = pesan;
  notif.style.background = warna;
  notif.classList.remove("hidden");
  notif.classList.add("show");

  setTimeout(() => {
    notif.classList.remove("show");
    setTimeout(() => notif.classList.add("hidden"), 300);
  }, 3000);
}

/* ====== DATA BERANDA (bisa kamu ubah sewaktu-waktu) ====== */
const polresAddress = "Jl. Contoh No. 123, Kecamatan Contoh, Kota Contoh, 40123";

const kepalaSDM = {
  nama: "AKBP Rudi Pratama, S.I.K.",
  jabatan: "Kepala SDM",
  foto: "kepala-sdm.jpg" // siapkan file ini
};

const kepalaBagian = [
  { nama: "Kompol Dewa Saputra", jabatan: "Kabagian Perencanaan", foto: "kabag-1.jpg" },
  { nama: "Kompol Siti Rahma",   jabatan: "Kabagian Pembinaan",   foto: "kabag-2.jpg" },
  { nama: "Kompol Aditya Putra", jabatan: "Kabagian Rekrutmen",   foto: "kabag-3.jpg" },
  { nama: "Kompol Maya Lestari", jabatan: "Kabagian Diklat",      foto: "kabag-4.jpg" }
];

/* ====== RENDER BERANDA ====== */
function renderHome() {
  // alamat
  const alamatEl = document.getElementById("alamat-polres");
  if (alamatEl) alamatEl.textContent = polresAddress;

  // kepala SDM
  const kepalaEl = document.getElementById("kepala-sdm");
  if (kepalaEl) {
    kepalaEl.innerHTML = `
      <img src="${kepalaSDM.foto}" alt="${kepalaSDM.nama}">
      <div>
        <p class="name">${kepalaSDM.nama}</p>
        <p class="role">${kepalaSDM.jabatan}</p>
      </div>
    `;
    kepalaEl.classList.add("person");
  }

  // kepala bagian
  const gridEl = document.getElementById("kepala-bagian");
  if (gridEl) {
    gridEl.innerHTML = kepalaBagian.map(p => `
      <div class="person">
        <img src="${p.foto}" alt="${p.nama}">
        <div>
          <p class="name">${p.nama}</p>
          <p class="role">${p.jabatan}</p>
        </div>
      </div>
    `).join('');
  }

  // teaser 3 berita terbaru
  const homeNews = document.getElementById("berita-home");
  if (homeNews) {
    const take = berita.slice(0, 3); // pakai array berita yang sudah ada
    homeNews.innerHTML = take.length
      ? take.map(b => {
          let mediaHTML = "";
          if (b.media) {
            if (b.type && b.type.startsWith("image")) {
              mediaHTML = `<img src="${b.media}" alt="">`;
            } else if (b.type && b.type.startsWith("video")) {
              mediaHTML = `<video controls><source src="${b.media}" type="${b.type}"></video>`;
            }
          }
          return `
            <div class="card">
              <strong>${b.judul}</strong>
              <p>${b.isi}</p>
              ${mediaHTML}
            </div>
          `;
        }).join("")
      : `<div class="card"><em>Belum ada berita. Tambahkan melalui panel Admin.</em></div>`;
  }
}

/* ====== Init ====== */
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const res = await fetch('php/check_session.php');
    const s = await res.json();
    adminLoggedIn = !!s.logged_in;
    if (adminLoggedIn) {
      document.getElementById("login-button").classList.add("hidden");
      document.getElementById("logout-button").classList.remove("hidden");
      document.getElementById("login-title").textContent = "Admin Aktif";
    }
  } catch(e) {
    console.error("Gagal cek sesi", e);
  }

  tampilkanBerita();
  tampilkanStruktur();
  renderHome && renderHome(); // kalau ada fungsi renderHome
});
