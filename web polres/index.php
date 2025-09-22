<?php
session_start();
$isLoggedIn   = isset($_SESSION['admin_id']);
$adminUser    = $isLoggedIn ? ($_SESSION['admin_username'] ?? '') : '';
$adminFull    = $isLoggedIn ? ($_SESSION['admin_fullname'] ?? $adminUser) : '';
$adminRole    = $isLoggedIn ? ($_SESSION['admin_role'] ?? 'admin') : '';

// ================== ROUTER API (POST JSON) ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['r']) && $_GET['r'] === 'kepala_list') {
  header('Content-Type: application/json; charset=utf-8');

  try {
    require __DIR__ . '/config.php'; // harus set $pdo (PDO MySQL)
    if (!isset($pdo)) { throw new Exception('PDO tidak tersedia dari config.php'); }

    // Ambil JSON body
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?: [];
    $type = isset($body['type']) ? strtolower(trim($body['type'])) : '';

    // dukung published / published_only
    $pubOnly = null;
    if (array_key_exists('published', $body))       $pubOnly = (int)$body['published'];
    if (array_key_exists('published_only', $body))  $pubOnly = (int)$body['published_only'];

    // Tentukan tabel + kolom SELECT (sdm tidak punya kolom 'urutan')
    if ($type === 'bagian') {
      $table = 'kepala_bagian';
      $sql   = "SELECT id, nama, jabatan, deskripsi, foto_url, published, urutan, updated_at FROM {$table}";
    } elseif ($type === 'sdm') {
      $table = 'kepala_sdm';
      $sql   = "SELECT id, nama, jabatan, deskripsi, foto_url, published, updated_at FROM {$table}";
    } else {
      echo json_encode(['success'=>false,'data'=>[],'message'=>"type wajib 'bagian' atau 'sdm'"]);
      exit;
    }

    $params = [];
    if ($pubOnly !== null) {
      $sql .= " WHERE published = :pub";
      $params[':pub'] = $pubOnly ? 1 : 0;
    }
    // urutkan: bagian pakai urutan ASC, sdm pakai id DESC
    $sql .= ($type === 'bagian') ? " ORDER BY urutan ASC, id DESC" : " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success'=>true,'data'=>$rows]);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'data'=>[],'message'=>'DB error','detail'=>$e->getMessage()]);
  }
  exit; // hentikan render HTML untuk request API
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sistem Informasi</title>
  <link rel="stylesheet" href="style.css?v=2">
</head>
<body>
  <!-- NAVBAR -->
  <nav class="nav">
      <img src="bendera.png" alt="Bendera Merah Putih" class="logo">

    <!-- tombol hamburger -->
    <button class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false">
      <span class="bar"></span>
      <span class="bar"></span>
      <span class="bar"></span>
    </button>

    <!-- menu utama -->
    <ul class="nav-menu" id="navMenu">
      <li><a href="#" onclick="showSection('home'); closeNav()">Beranda</a></li>
      <li><a href="#" onclick="showSection('struktur'); loadStruktur(); closeNav()">Struktur</a></li>
      <li><a href="#" onclick="showSection('berita'); loadBerita(); closeNav()">Berita</a></li>
      <li><a href="#" onclick="showSection('admin'); closeNav()">Admin</a></li>
    </ul>
  </nav>

  <!-- LOGIN / LOGOUT MINI PANEL -->
  <section id="login-admin" class="hidden">
    <h2 id="login-title">Login Admin</h2>

    <?php if(!$isLoggedIn): ?>
      <input type="text" id="username" placeholder="Username"><br />
      <input type="password" id="password" placeholder="Password"><br />
      <div class="toolbar">
        <button class="primary" id="login-button" onclick="prosesLogin()">Login</button>
      </div>
      <p id="login-error" class="muted" style="color:#dc2626;"></p>
    <?php else: ?>
      <p>Sudah login sebagai <strong><?= htmlspecialchars($adminFull ?: $adminUser) ?></strong> (<?= htmlspecialchars($adminRole) ?>).</p>
      <div class="toolbar">
        <button class="ghost" onclick="logout()">Logout</button>
        <button class="primary" onclick="showSection('berita'); loadBerita()">Kelola Berita</button>
      </div>
    <?php endif; ?>
  </section>

  <!-- HOME -->
  <section id="home">
    <!-- HERO -->
    <div class="hero">
      <div class="hero-left">
        <img src="sdm-polri.png" alt="Logo SDM Polri" class="logo-sdm">
        <div class="hero-text">
          <h1 class="title">Biro SDM Polri</h1>
          <p id="alamat-text">
          Jl. Gajah Mada No.99, Menanggal, Kec. Mojosari,<br> 
          Kabupaten Mojokerto, Jawa Timur 61382<br>
          </p>
        </div>
      </div>
      <div class="hero-right">
        <img src="polres-logo.png" alt="Logo Polres" class="logo-polres">
      </div>
    </div>

    <!-- PROFIL KEPALA SDM -->
    <section class="block">
      <h2>Kepala SDM</h2>
      <div id="kepala-sdm" class="people-feature"></div>
    </section>

    <!-- KEPALA BAGIAN SDM -->
    <section class="block">
      <h2>Kepala Bagian</h2>
      <div id="kepala-bagian" class="people-grid"></div>
    </section>

    <!-- VISI & MISI -->
    <section class="block">
      <h2>Visi & Misi</h2>
      <div class="visi-misi">
        <div class="card">
          <h3>Visi</h3>
          <p id="visi-text">
            Terwujudnya Indonesia yang Aman dan Tertib (mendukung Indonesia Maju, Berdaulat, Mandiri, Berkepribadian, Berlandaskan Gotong-Royong)
          </p>
        </div>
        <div class="card">
          <h3>Misi</h3>
          <p id="misi-text">
           1. Melindungi, mengayomi, melayani.<br>
           2. Dorong kemajuan budaya.<br>
           3. Tegakkan hukum anti-korupsi, bermartabat, terpercaya.<br>
           4. Jaga lingkungan hidup berkelanjutan.<br>
           5. Janji Presiden: penegakan hukum terhadap kriminal besar, sinergi, profesionalisme & reformasi.<br>
          </p>
        </div>
        <div class="card">
          <h3>Tujuan</h3>
          <p id="tujuan-text">
            1. Amankan dan tertibkan masyarakat.<br>
            2. Tegakkan hukum secara adil.<br>
            3. Profesionalisasi Polri.<br>
            4. Modernisasi pelayanan.<br>
            5. Manajemen terintegrasi & terpercaya.<br>
          </p>
        </div>
      </div>
    </section>

    <!-- TEASER BERITA -->
    <section class="block">
      <div class="block-head">
        <h2>Berita Terbaru</h2>
        <a href="#" onclick="showSection('berita'); loadBerita()" class="link">Lihat semua →</a>
      </div>
      <div id="berita-home" class="grid"></div>
    </section>
  </section>

  <!-- STRUKTUR -->
  <section id="struktur" class="hidden">
    <h2>Struktur Organisasi</h2>
    <div id="daftar-struktur" class="grid"></div>
  </section>

  <!-- BERITA -->
  <section id="berita" class="hidden">
    <h2>Berita Terbaru</h2>
    <div id="daftar-berita" class="grid"></div>
  </section>

  <!-- ADMIN -->
<section id="admin" class="hidden">
  <h2>Panel Admin</h2>

  <div id="akses-admin" class="<?= $isLoggedIn ? 'hidden' : '' ?>">
    <p class="muted">Silakan login untuk mengelola berita.</p>
    <button class="primary" onclick="showSection('login-admin')">Buka Form Login</button>
  </div>

  <div id="form-admin" class="<?= $isLoggedIn ? '' : 'hidden' ?>">
    <input type="text" id="judul" placeholder="Judul Berita" /><br />
    <textarea id="isi" placeholder="Isi berita..." ></textarea><br />
    <input type="file" id="media" accept="image/*,video/*"><br />

    <div class="toolbar">
      <button id="submit-berita" class="primary" onclick="tambahBerita()">Tambah Berita</button>
      <button id="cancel-edit" class="ghost hidden" onclick="batalEdit()">Batal</button>
      <button class="ghost" onclick="logout()">Logout Admin</button>
    </div>
    <p class="muted" id="admin-hint">Saat mode edit aktif, tombol di atas berubah menjadi “Simpan Perubahan”.</p>
  </div>
  
  <?php if($isLoggedIn): ?>
  <!-- === FORM Kepala SDM === -->
  <section class="block admin-sub" style="margin-top:24px">
    <h3>Kelola Kepala SDM</h3>
    <input type="hidden" id="k_sdm_id" value="">
    <input type="text" id="k_sdm_nama" placeholder="Nama"><br/>
    <input type="text" id="k_sdm_jabatan" placeholder="Jabatan" value="Kepala SDM"><br/>
    <textarea id="k_sdm_deskripsi" placeholder="Deskripsi singkat..."></textarea><br/>
    <input type="file" id="k_sdm_foto" accept="image/*"><br/>
    <label class="muted">
      <input type="checkbox" id="k_sdm_published" checked> Terbitkan
    </label>
    <div class="toolbar">
      <button class="primary" onclick="simpanKepalaSDM()">Simpan</button>
      <button class="ghost" onclick="muatKepalaSDMKeForm()">Muat Data</button>
      <button id="btnHapusSDM" class="danger hidden" type="button" onclick="hapusSDMFromForm()">Hapus</button>
    </div>
    <p class="muted">Gunakan “Muat Data” untuk mengambil data saat ini ke form.</p>
  </section>

  <!-- === FORM Kepala Bagian === -->
  <section class="block admin-sub" style="margin-top:24px">
    <h3>Kelola Kepala Bagian</h3>
    <form id="formKepala" enctype="multipart/form-data">
      <input type="hidden" name="type" value="bagian">
      <input type="hidden" id="k_bg_id" name="id" value="">
      <input type="text" id="k_bg_nama" name="nama" placeholder="Nama"><br/>
      <input type="text" id="k_bg_jabatan" name="jabatan" placeholder="Jabatan" value="Kepala Bagian"><br/>
      <textarea id="k_bg_deskripsi" name="deskripsi" placeholder="Deskripsi singkat..."></textarea><br/>
      <input type="number" id="k_bg_urutan" name="urutan" placeholder="Urutan" value="1" min="1"><br/>
      <input type="file" id="k_bg_foto" name="foto" accept="image/*"><br/>
      <label class="muted">
        <input type="checkbox" id="k_bg_published" name="published" value="1" checked> Terbitkan
      </label>
      <div class="toolbar">
        <button class="primary" type="submit">Tambah/Update</button>
        <button class="ghost" type="button" onclick="batalEditBagian()">Batal</button>
        <button id="btnHapusBagian" class="danger hidden" type="button" onclick="hapusBagianFromForm()">Hapus</button>
      </div>
    </form>
    <p class="muted">Klik “Edit” pada kartu untuk memuat data ke form ini.</p>
  </section>
  <?php endif; ?>

  <!-- === LIST Kepala Bagian (selalu tampil) === -->
  <section class="block admin-sub" style="margin-top:16px">
    <h3>Daftar Kepala Bagian</h3>
    <div id="kepalaList" class="people-grid"></div>
  </section>

  <?php if($isLoggedIn): ?>
  <!-- === FORM STRUKTUR === -->
  <section class="block admin-sub" style="margin-top:24px">
    <h3>Kelola Struktur</h3>
    <div>
      <input type="text" id="s_nama" placeholder="Nama"><br />
      <input type="text" id="s_jabatan" placeholder="Jabatan"><br />
      <input type="number" id="s_urutan" placeholder="Urutan" value="1" min="1"><br />
      <input type="file" id="s_foto" accept="image/*"><br />
      <label class="muted">
        <input type="checkbox" id="s_published" checked> Terbitkan
      </label>
      <div class="toolbar">
        <button id="s_submit" class="primary" onclick="simpanStruktur()">Tambah Struktur</button>
        <button id="s_cancel" class="ghost hidden" onclick="batalEditStruktur()">Batal</button>
      </div>
      <p class="muted" id="s_hint">Saat mode edit aktif, tombol di atas berubah menjadi “Simpan Perubahan”.</p>
    </div>
  </section>
  <?php endif; ?>
</section>


    <!-- Notifikasi (toast) -->
    <div id="notif" class="hidden"></div>
  </section>

  <script>
    // ==== STATE DARI PHP ====
    window.IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;

// ==== NAV (konsisten pakai .open) ====
  const navToggle = document.getElementById('navToggle');
  const navMenu = document.getElementById('navMenu');

  navToggle?.addEventListener('click', () => {
    const open = navMenu.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', String(open));
  });

  function closeNav(){
    navMenu.classList.remove('open');
    navToggle?.setAttribute('aria-expanded', 'false');
  }

    // ==== SECTION SWITCHER ====
    function showSection(id){
      document.querySelectorAll('section').forEach(s => { if(s.id) s.classList.add('hidden'); });
      document.getElementById(id)?.classList.remove('hidden');
      if(id==='home'){ 
        loadHomeBerita(); 
        loadKepalaSDM(); 
        loadKepalaBagian(); 
      }
      if(id==='berita'){ loadBerita(); }
      if(id==='struktur'){ loadStruktur(); }
      if(id==='login-admin'){ const e=document.getElementById('login-error'); if(e) e.textContent=''; }
    }

    // === tambahkan ini di bawahnya ===
    window.showSection = showSection;
    window.closeNav   = closeNav;

    // ==== LOGIN ====
    async function prosesLogin(){
      const username = (document.getElementById('username')||{}).value?.trim();
      const password = (document.getElementById('password')||{}).value || '';
      const err = document.getElementById('login-error');
      if(err) err.textContent = '';
      if(!username || !password){ if(err) err.textContent='Username dan password wajib diisi.'; return; }
      try{
        const res = await fetch('login.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({username, password})
        });
        const data = await res.json();
        if(data.success){ location.reload(); }
        else { if(err) err.textContent = data.message || 'Login gagal.'; }
      }catch(e){ if(err) err.textContent='Gagal menghubungi server.'; }
    }
    async function logout(){ try{ await fetch('logout.php',{method:'POST'}); }catch(e){} location.reload(); }

    // ==== RENDER UTILS ====
    function el(html){ const t=document.createElement('template'); t.innerHTML=html.trim(); return t.content.firstChild; }
    function fmt(s){ try{ return new Date(s).toLocaleString('id-ID'); }catch(_){ return s; } }

    // ==== HOME: 3 berita terbaru (terbit saja) ====
    async function loadHomeBerita(){
      try{
        const res = await fetch('berita_list.php?limit=3&published_only=1');
        const {data=[]} = await res.json();
        const wrap = document.getElementById('berita-home'); wrap.innerHTML='';
        data.forEach(b => wrap.appendChild(el(`
          <article class="card">
            <h3>${b.title}</h3>
            <div class="muted" style="font-size:.85rem">${fmt(b.created_at)}</div>
            ${b.media_url ? (b.media_url.match(/\\.(mp4|webm|ogg)$/i) ? 
              `<video src="${b.media_url}" controls style="width:100%;border-radius:10px"></video>` :
              `<img src="${b.media_url}" alt="" style="max-width:100%;border-radius:10px">`) : ''}
            <p>${b.content.substring(0,140)}${b.content.length>140?'...':''}</p>
          </article>`)));
      }catch(e){}
    }
    
function asSrc(path){
  if (!path) return '';
  let p = String(path).trim();

  // absolut? biarkan (http(s):// atau data:)
  if (/^(https?:)?\/\//i.test(p) || /^data:/i.test(p)) return p;

  // buang ./ atau / di depan
  p = p.replace(/^[./]+/, '');

  // kompres "uploads/uploads/..." -> "uploads/..."
  p = p.replace(/^(?:uploads\/)+/i, 'uploads/');

  // pastikan ada satu "uploads/" di depan
  if (!/^uploads\//i.test(p)) p = 'uploads/' + p;

  return p;
}

// ====== GANTI FUNGSI INI DENGAN YANG BARU ======
async function loadKepalaSDM(){
  try{
    const res = await fetch('index.php?r=kepala_list', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      cache: 'no-store',
      body: JSON.stringify(
        window.IS_LOGGED_IN
          ? { type: 'sdm' }                    // admin lihat semua
          : { type: 'sdm', published_only: 1 } // publik hanya published
      )
    });
    const { data = [] } = await res.json();

    const wrap = document.getElementById('kepala-sdm');
    wrap.innerHTML = '';

    if (!data.length){
      wrap.innerHTML = '<p class="muted">Belum ada data.</p>';
      return;
    }

    const k = data[0];
    const tools = window.IS_LOGGED_IN ? `
      <div class="toolbar">
        <button onclick='mulaiEditKepalaSDM(${JSON.stringify(k).replace(/'/g,"&#39;")})'>Edit</button>
        <button class="danger" onclick="hapusKepalaSDM(${k.id})">Hapus</button>
      </div>` : '';

    wrap.innerHTML = `
      <article class="card person">
        ${k.foto_url ? `<img src="${asSrc(k.foto_url)}" alt="${k.nama}" class="avatar-lg">` : ''}
        <div>
          <h3>${k.nama || 'Belum diisi'}</h3>
          <div class="muted">${k.jabatan || 'Kepala SDM'}</div>
          ${k.deskripsi ? `<p>${k.deskripsi}</p>` : ''}
          <div class="muted">ID: ${k.id}</div>
        </div>
        ${tools}
      </article>
    `;
  }catch(e){
    document.getElementById('kepala-sdm').innerHTML = '<p>Gagal memuat.</p>';
  }
}

// expose ke global
window.showSection   = showSection;
window.closeNav      = closeNav;
window.loadKepalaSDM = loadKepalaSDM;
window.loadKepalaBagian = loadKepalaBagian;

async function loadKepalaBagian(){
  try{
const res = await fetch('index.php?r=kepala_list', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(
    window.IS_LOGGED_IN
      ? { type: 'bagian' }
      : { type: 'bagian', published_only: 1 }
  )
});

    const {data=[]} = await res.json();
    const wrap = document.getElementById('kepala-bagian');
    wrap.innerHTML = '';

    if (!data.length){
      wrap.innerHTML = '<p class="muted">Belum ada data.</p>';
      return;
    }

    data.forEach(p => {
      const tools = window.IS_LOGGED_IN ? `
        <div class="toolbar">
          <button onclick='mulaiEditBagian(${JSON.stringify(p).replace(/'/g,"&#39;")})'>Edit</button>
          <button class="danger" onclick="hapusBagian(${p.id})">Hapus</button>
        </div>` : '';

      wrap.insertAdjacentHTML('beforeend', `
        <article class="card person">
          ${p.foto_url ? `<img src="${asSrc(p.foto_url)}" alt="${p.nama}" class="avatar">` : ''}
          <h3>${p.nama}</h3>
          <div class="muted">${p.jabatan || 'Kepala Bagian'}</div>
          ${p.deskripsi ? `<p>${p.deskripsi}</p>` : ''}
          ${tools}
        </article>
      `);
    });
  }catch(e){
    document.getElementById('kepala-bagian').innerHTML = '<p>Gagal memuat.</p>';
  }
}

// --- UTIL form helper ---
function _val(id){ return (document.getElementById(id)||{}).value || ''; }
function _checked(id){ const el=document.getElementById(id); return el && el.checked ? 1 : 0; }

// =================== KEPALA SDM ===================

// Isi form dari kartu (dipanggil saat klik tombol Edit)
function mulaiEditKepalaSDM(k){
  document.getElementById('k_sdm_id').value        = k.id || '';
  document.getElementById('k_sdm_nama').value      = k.nama || '';
  document.getElementById('k_sdm_jabatan').value   = k.jabatan || 'Kepala SDM';
  document.getElementById('k_sdm_deskripsi').value = k.deskripsi || '';
  document.getElementById('k_sdm_published').checked = String(k.published)==='1';
  const f=document.getElementById('k_sdm_foto'); if (f) f.value='';

  // 👉 tampilkan tombol hapus saat mode edit
  document.getElementById('btnHapusSDM')?.classList.remove('hidden');

  showSection('admin'); 
  showToast('Mode edit Kepala SDM.');
}


// Ambil record SDM terbaru dari server ke form (tombol "Muat Data")
async function muatKepalaSDMKeForm(){
  try{
    const res = await fetch('index.php?r=kepala_list', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ type: 'sdm' })
});
 
    const {data=[]} = await res.json();
    if(!data.length){ showToast('Belum ada data SDM.', true); return; }
    mulaiEditKepalaSDM(data[0]);
  }catch(e){ showToast('Gagal memuat data SDM.', true); }
}

// Simpan/Update SDM (kirim multipart untuk dukung file)
async function simpanKepalaSDM(){
  if(!window.IS_LOGGED_IN){ alert('Harus login.'); return; }

  const fd = new FormData();
  fd.append('type', 'sdm');
  fd.append('id', _val('k_sdm_id'));
  fd.append('nama', _val('k_sdm_nama'));
  fd.append('jabatan', _val('k_sdm_jabatan') || 'Kepala SDM');
  fd.append('deskripsi', _val('k_sdm_deskripsi'));
  fd.append('published', _checked('k_sdm_published'));
  const file = (document.getElementById('k_sdm_foto')||{}).files?.[0];
  if (file) fd.append('foto', file);

  try{
    const res = await fetch('kepala_save.php', { method:'POST', body: fd });
    if (res.status === 401){ alert('Sesi login habis. Silakan login lagi.'); return; }
    const data = await res.json();
    if(data.success){
      await loadKepalaSDM();
      showToast('Kepala SDM tersimpan.');
      // opsional reset id agar kembali ke mode tambah
      // document.getElementById('k_sdm_id').value = '';
    } else {
      showToast(data.message || 'Gagal menyimpan SDM.', true);
    }
  }catch(e){ showToast('Gagal menyimpan SDM.', true); }
}

// Hapus SDM (sudah ada di proyekmu, ini versi aman)
async function hapusKepalaSDM(id){
  console.log('hapusKepalaSDM CALLED, id=', id);   // ← tambah baris ini
  if (!window.IS_LOGGED_IN){ alert('Harus login.'); return; }
  if (!confirm('Hapus data Kepala SDM ini?')) return;

  try{
    const res = await fetch('kepala_delete.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ type:'sdm', id })
    });

    // log mentah untuk debugging
    const raw = await res.text();
    let data;
    try { data = JSON.parse(raw); } catch { data = { success:false, message:'Response bukan JSON', raw }; }
    console.log('hapusKepalaSDM →', res.status, data);

    if (res.status === 401){
      alert('Sesi login habis. Silakan login lagi.');
      return;
    }

    if (data.success){
      await loadKepalaSDM();          // refresh tampilan setelah hapus
      showToast('Kepala SDM dihapus.');
    } else {
      alert('Gagal hapus: ' + (data.message || data.raw || 'Tidak diketahui'));
    }
  }catch(e){
    alert('Error hapus: ' + e.message);
  }
}

function hapusSDMFromForm(){
  const id = document.getElementById('k_sdm_id')?.value;
  if (!id) { showToast('Belum ada data untuk dihapus.', true); return; }
  hapusKepalaSDM(Number(id)); // panggil fungsi hapusKepalaSDM yang sudah ada
}


// =================== KEPALA BAGIAN ===================

// Klik Edit pada kartu → isi form
function mulaiEditBagian(p){
  document.getElementById('k_bg_id').value        = p.id || '';
  document.getElementById('k_bg_nama').value      = p.nama || '';
  document.getElementById('k_bg_jabatan').value   = p.jabatan || 'Kepala Bagian';
  document.getElementById('k_bg_deskripsi').value = p.deskripsi || '';
  document.getElementById('k_bg_urutan').value    = p.urutan || 1;
  document.getElementById('k_bg_published').checked = String(p.published)==='1';
  const f=document.getElementById('k_bg_foto'); if (f) f.value='';
  showToast('Mode edit Kepala Bagian.');
}

/* ====== SIMPAN / UPDATE Kepala Bagian ====== */
async function simpanBagian(fd){
  if(!window.IS_LOGGED_IN){ alert('Harus login.'); return; }

  try{
    const res  = await fetch('kepala_save.php', { method:'POST', body: fd });
    if (res.status === 401){ alert('Sesi login habis. Silakan login lagi.'); return; }
    const data = await res.json();

    if (data.success){
      await loadKepalaBagian();  // refresh grid di beranda
      await loadKepalaList();    // refresh daftar di panel admin
      batalEditBagian();         // reset form (tombol hapus disembunyikan lagi)
      showToast('Kepala Bagian tersimpan.');
    } else {
      showToast(data.message || 'Gagal menyimpan Bagian.', true);
    }
  }catch(e){
    showToast('Gagal menyimpan Bagian.', true);
  }
}

/* ====== Submit handler form "Kelola Kepala Bagian" ====== */
document.getElementById('formKepala')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const form = e.currentTarget;
  const fd   = new FormData(form);

  // pastikan type & published terkirim sesuai checkbox
  if(!fd.has('type')) fd.set('type', 'bagian');
  if(!form.querySelector('#k_bg_published').checked){
    fd.set('published', '0');
  }
  await simpanBagian(fd);
});


function mulaiEditBagian(p){
  document.getElementById('k_bg_id').value        = p.id || '';
  document.getElementById('k_bg_nama').value      = p.nama || '';
  document.getElementById('k_bg_jabatan').value   = p.jabatan || 'Kepala Bagian';
  document.getElementById('k_bg_deskripsi').value = p.deskripsi || '';
  document.getElementById('k_bg_urutan').value    = p.urutan || 1;
  document.getElementById('k_bg_published').checked = String(p.published)==='1';
  const f=document.getElementById('k_bg_foto'); if (f) f.value='';

  // tampilkan tombol Hapus saat mode edit
  document.getElementById('btnHapusBagian')?.classList.remove('hidden');

  showToast('Mode edit Kepala Bagian.');
}

function batalEditBagian(){
  ['k_bg_id','k_bg_nama','k_bg_jabatan','k_bg_deskripsi','k_bg_urutan'].forEach(id=>{
    const el=document.getElementById(id); if(el) el.value='';
  });
  const elU=document.getElementById('k_bg_urutan');    if(elU) elU.value='1';
  const elP=document.getElementById('k_bg_published'); if(elP) elP.checked=true;
  const elF=document.getElementById('k_bg_foto');      if(elF) elF.value='';

  // sembunyikan tombol Hapus lagi
  document.getElementById('btnHapusBagian')?.classList.add('hidden');
}

function hapusBagianFromForm(){
  const id = document.getElementById('k_bg_id')?.value;
  if (!id) { showToast('Belum ada data untuk dihapus.', true); return; }
  hapusBagian(Number(id)); // pakai fungsi hapusBagian(id) yang sudah ada
}


/* ====== Hapus 1 record Kepala Bagian (final) ====== */
async function hapusBagian(id){
  if(!window.IS_LOGGED_IN){ alert('Harus login.'); return; }
  if(!confirm('Hapus data Kepala Bagian ini?')) return;

  try{
    const res  = await fetch('kepala_delete.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ type:'bagian', id })
    });
    if (res.status === 401){ alert('Sesi login habis. Silakan login lagi.'); return; }
    const data = await res.json();

    if (data.success){
      await loadKepalaBagian();  // grid beranda
      await loadKepalaList();    // daftar admin
      batalEditBagian();         // kosongkan form & sembunyikan tombol hapus
      showToast('Kepala Bagian dihapus.');
    } else {
      showToast(data.message || 'Gagal menghapus Bagian.', true);
    }
  }catch(e){
    showToast('Gagal menghapus Bagian.', true);
  }
}


    // ==== BERITA LIST ====
    let editingId = '';
    async function loadBerita(){
      try{
        const url = window.IS_LOGGED_IN ? 'berita_list.php' : 'berita_list.php?published_only=1';
        const res = await fetch(url);
        const {data=[]} = await res.json();
        const wrap = document.getElementById('daftar-berita'); wrap.innerHTML='';

        data.forEach(b => {
  let media = '';
  if (b.media_url) {
    const isVideo = /\.(mp4|webm|ogg)$/i.test(b.media_url) || (b.media_mime && b.media_mime.startsWith('video/'));
    const isImage = /\.(jpe?g|png|gif|webp)$/i.test(b.media_url) || (b.media_mime && b.media_mime.startsWith('image/'));

    if (isVideo) {
      media = `
        <video class="news-media video" controls preload="metadata" playsinline>
   <source src="${b.media_url}">
          Browser Anda tidak mendukung video tag.
        </video>`;
    } else if (isImage) {
  media = `<img src="${asSrc(b.media_url)}" alt="" style="max-width:100%;border-radius:10px">`;
  }
  }

  const tools = window.IS_LOGGED_IN ? `
    <div class="toolbar">
      <button onclick='mulaiEdit(${JSON.stringify(b).replace(/'/g,"&#39;")})'>Edit</button>
      <button class="danger" onclick="hapusBerita(${b.id})">Hapus</button>
    </div>` : '';

  wrap.appendChild(el(`
    <article class="card">
      <h3>${b.title} ${b.published==1?'<span class="badge">Terbit</span>':'<span class="badge">Draft</span>'}</h3>
      <div class="muted" style="font-size:.85rem">${fmt(b.created_at)}</div>
      <div>${media}</div>
      <p>${b.content}</p>
      ${tools}
    </article>`));
});
      }catch(e){
        document.getElementById('daftar-berita').innerHTML = '<p>Gagal memuat berita.</p>';
      }
    }

    function mulaiEdit(b){
      editingId = String(b.id);
      document.getElementById('judul').value = b.title;
      document.getElementById('isi').value   = b.content;
      document.getElementById('submit-berita').textContent = 'Simpan Perubahan';
      document.getElementById('cancel-edit').classList.remove('hidden');
      showSection('admin');
    }
    function batalEdit(){
      editingId = '';
      document.getElementById('judul').value = '';
      document.getElementById('isi').value   = '';
      document.getElementById('media').value = '';
      document.getElementById('submit-berita').textContent = 'Tambah Berita';
      document.getElementById('cancel-edit').classList.add('hidden');
    }

    async function tambahBerita(){
      if(!window.IS_LOGGED_IN){ alert('Harus login.'); return; }
      const title = document.getElementById('judul').value.trim();
      const content = document.getElementById('isi').value.trim();
      if(!title || !content){ alert('Judul dan isi wajib diisi.'); return; }

      const fd = new FormData();
      if(editingId) fd.append('id', editingId);
      fd.append('title', title);
      fd.append('content', content);
      fd.append('published', '1'); // default terbit
      const media = document.getElementById('media').files[0];
      if(media) fd.append('media', media);

      try{
        const res = await fetch('berita_save.php', {method:'POST', body:fd});
        const data = await res.json();
        if(data.success){
          batalEdit();
          loadBerita();
          showToast('Berhasil disimpan.');
        }else{
          showToast(data.message || 'Gagal menyimpan.', true);
        }
      }catch(e){ showToast('Gagal menyimpan.', true); }
    }

    async function hapusBerita(id){
      if(!confirm('Hapus berita ini?')) return;
      try{
        const res = await fetch('berita_delete.php', {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({id})
        });
        const data = await res.json();
        if(data.success){ loadBerita(); showToast('Berhasil dihapus.'); }
        else { showToast(data.message || 'Gagal menghapus.', true); }
      }catch(e){ showToast('Gagal menghapus.', true); }
    }

    // ==== STRUKTUR LIST ====
   async function loadStruktur(){
  try{
    const url = window.IS_LOGGED_IN ? 'struktur_list.php' : 'struktur_list.php?published_only=1';
    const res = await fetch(url);
    const {data=[]} = await res.json();
    const wrap = document.getElementById('daftar-struktur'); 
    wrap.innerHTML='';

    data.forEach(s => {
      const tools = window.IS_LOGGED_IN ? `
        <div class="toolbar">
          <button onclick='mulaiEditStruktur(${JSON.stringify(s).replace(/'/g,"&#39;")})'>Edit</button>
          <button class="danger" onclick="hapusStruktur(${s.id})">Hapus</button>
        </div>` : '';

      wrap.appendChild(el(`
        <article class="card">
          <h3>${s.nama} <span class="muted">(${s.jabatan})</span>
            ${s.published==1?'<span class="badge">Terbit</span>':'<span class="badge">Draft</span>'}
          </h3>
         ${s.foto_url ? `<img src="${asSrc(s.foto_url)}" alt="" style="max-width:100%;border-radius:10px">` : ''}
          ${tools}
        </article>`));
    });

  }catch(e){
    document.getElementById('daftar-struktur').innerHTML = '<p>Gagal memuat struktur.</p>';
  }
}

    // ==== TOAST NOTIF ====
    function showToast(msg, error){
      const n=document.getElementById('notif');
      n.textContent=msg; n.classList.remove('hidden');
      if(error){ n.style.color='#dc2626'; } else { n.style.color='inherit'; }
      setTimeout(()=>n.classList.add('hidden'), 2000);
    }

    // ==== INIT ====
    const ap = document.getElementById('alamat-polres');
    if (ap) ap.textContent = 'Alamat Polres akan ditampilkan di sini.';
    const misi = document.getElementById('misi-list'); if(misi){ misi.innerHTML = '<li>Meningkatkan kompetensi SDM</li><li>Penguatan sistem karier</li><li>Digitalisasi layanan</li>'; }
    showSection('home'); 
    loadHomeBerita(); 
    loadKepalaSDM(); 
    loadKepalaBagian();
  </script>
  <!-- ====== SCRIPT STRUKTUR CRUD (Admin) ====== -->
<script>
let editingStrukturId = '';

function mulaiEditStruktur(s){
  editingStrukturId = String(s.id);
  document.getElementById('s_nama').value = s.nama || '';
  document.getElementById('s_jabatan').value = s.jabatan || '';
  document.getElementById('s_urutan').value = s.urutan || 1;
  document.getElementById('s_published').checked = String(s.published) === '1';
  document.getElementById('s_foto').value = '';
  document.getElementById('s_submit').textContent = 'Simpan Perubahan';
  document.getElementById('s_cancel').classList.remove('hidden');
  document.getElementById('s_hint').textContent = 'Sedang mengedit struktur. Ubah data dan (opsional) ganti foto.';
  showSection('admin');
}

function batalEditStruktur(){
  editingStrukturId = '';
  document.getElementById('s_nama').value = '';
  document.getElementById('s_jabatan').value = '';
  document.getElementById('s_urutan').value = 1;
  document.getElementById('s_published').checked = true;
  document.getElementById('s_foto').value = '';
  document.getElementById('s_submit').textContent = 'Tambah Struktur';
  document.getElementById('s_cancel').classList.add('hidden');
  document.getElementById('s_hint').textContent = 'Saat mode edit aktif, tombol di atas berubah menjadi “Simpan Perubahan”.';
}

async function simpanStruktur(){
  if(!window.IS_LOGGED_IN){ alert('Harus login.'); return; }
  const nama = document.getElementById('s_nama').value.trim();
  const jabatan = document.getElementById('s_jabatan').value.trim();
  const urutan = document.getElementById('s_urutan').value || '1';
  const published = document.getElementById('s_published').checked ? '1' : '0';
  if(!nama || !jabatan){ showToast('Nama dan jabatan wajib diisi.', true); return; }

  const fd = new FormData();
  if(editingStrukturId) fd.append('id', editingStrukturId);
  fd.append('nama', nama);
  fd.append('jabatan', jabatan);
  fd.append('urutan', urutan);
  fd.append('published', published);
  const foto = document.getElementById('s_foto').files[0];
  if(foto) fd.append('foto', foto);

  try{
    const res = await fetch('struktur_save.php', { method:'POST', body: fd });
    const data = await res.json();
    if(data.success){
      batalEditStruktur();
      loadStruktur();
      showToast('Struktur tersimpan.');
    }else{
      showToast(data.message || 'Gagal menyimpan struktur.', true);
    }
  }catch(e){
    showToast('Gagal menyimpan struktur.', true);
  }
}

async function hapusStruktur(id){
  if(!window.IS_LOGGED_IN){ alert('Harus login.'); return; }
  if(!confirm('Hapus struktur ini?')) return;
  try{
    const res = await fetch('struktur_delete.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ id })
    });
    const data = await res.json();
    if(data.success){
      loadStruktur();
      showToast('Struktur dihapus.');
    }else{
      showToast(data.message || 'Gagal menghapus struktur.', true);
    }
  }catch(e){
    showToast('Gagal menghapus struktur.', true);
  }
}
</script>

<script>
async function loadKepalaList(){
  try {
    const res = await fetch('index.php?r=kepala_list', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      cache: 'no-store',
      body: JSON.stringify(
        window.IS_LOGGED_IN
          ? { type: 'bagian' }                  // admin lihat semua
          : { type: 'bagian', published: 1 }    // publik cuma published
      )
    });
    const json = await res.json(); // ← WAJIB ada baris ini
    if (!json.success) throw new Error(json.detail || json.message || 'Gagal load list');

    const container = document.getElementById('kepalaList');
    container.innerHTML = json.data.map(row => `
      <article class="card person">
        ${row.foto_url ? `<img src="${asSrc(row.foto_url)}" alt="${row.nama}" class="avatar">` : ''}
        <h3>${row.nama}</h3>
        <div class="muted">${row.jabatan || 'Kepala Bagian'}</div>
        ${row.deskripsi ? `<p>${row.deskripsi}</p>` : ''}

        ${window.IS_LOGGED_IN ? `
          <div class="toolbar">
            <button onclick='mulaiEditBagian(${JSON.stringify(row).replace(/'/g,"&#39;")})'>Edit</button>
            <button class="danger" onclick="hapusBagian(${row.id})">Hapus</button>
          </div>` : ''}
      </article>
    `).join('');
  } catch (e) {
    console.error('loadKepalaList error:', e);
    alert('Gagal memuat daftar kepala');
  }
}

// panggil otomatis saat halaman siap
document.addEventListener('DOMContentLoaded', loadKepalaList);
</script>

</body>
</html>
