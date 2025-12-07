<?php /* portal_calendario.php — frontend com iframe do Google Web App */ ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Portal Bombeiro — Minha Escala</title>

  <style>
    :root{
      --bg: #0f172a;
      --panel: #111827;
      --border: #1f2937;
      --text: #e5e7eb;
      --muted: #94a3b8;
      --radius: 12px;
    }
    *{ box-sizing:border-box }
    html,body{ height:100% }
    body{ margin:0; background:var(--bg); color:var(--text); font-family:system-ui,Arial,Segoe UI,Roboto }
    .app{ display:grid; grid-template-columns:240px 1fr; min-height:100vh }

    /* SIDEBAR */
    .sidebar{ background:#0b1220; border-right:1px solid var(--border); padding:18px 14px }
    .logo{ display:flex; align-items:center; gap:10px; margin-bottom:22px }
    .logo img{ width:40px; height:40px; object-fit:contain }
    .logo-title{ font-weight:700 }
    .nav{ display:flex; flex-direction:column; gap:8px }
    .nav a{
      display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px;
      color:var(--text); text-decoration:none; border:1px solid transparent; transition:.15s
    }
    .nav a:hover{ background:#0e1526; border-color:var(--border) }
    .nav a.active{ background:#0e1526; border-color:#22304b }
    .ico{ width:18px; height:18px; opacity:.85 }

    /* TOPBAR */
    .topbar{
      position:sticky; top:0; z-index:20;
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 20px; border-bottom:1px solid var(--border); background:var(--bg)
    }
    .brand{ display:flex; align-items:center; gap:10px; font-weight:700 }
    .brand img{ width:28px; height:28px; object-fit:contain }
    .profile{ display:flex; align-items:center; gap:12px; cursor:pointer; color:var(--muted) }
    .avatar{ width:34px; height:34px; border-radius:50%; background:#22304b; display:grid; place-items:center; font-weight:700 }

    /* MAIN */
    .main{ padding:20px }
    .container{ max-width:1400px; margin:0 auto }
    .card{ background:var(--panel); border:1px solid var(--border); border-radius:var(--radius); padding:16px }

    /* iframe do calendário */
    .calendar-frame{
      width:100%;
      height:820px;          /* ajuste se quiser maior/menor */
      border:0;
      border-radius:12px;
      background:transparent;
      overflow:hidden;
    }

    /* MODAL PERFIL (mesmo visual de antes) */
    .modal-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.6); display:none; align-items:center; justify-content:center; z-index:50 }
    .modal{ width:min(900px,96vw); max-height:90vh; overflow:auto; background:var(--panel); border:1px solid var(--border); border-radius:14px }
    .modal header{ display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-bottom:1px solid var(--border) }
    .modal h3{ margin:0; font-size:18px }
    .modal .content{ padding:16px }
    .grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:12px }
    .field label{ display:block; font-size:12px; color:var(--muted); margin-bottom:6px }
    .field input, .field select, .field textarea{
      width:100%; padding:10px 12px; border-radius:10px; background:#0e1526; color:var(--text); border:1px solid var(--border)
    }
    .section{ border:1px solid var(--border); border-radius:12px; padding:12px; margin-bottom:12px }
    .section h4{ margin:0 0 10px 0; font-size:14px }
    .modal footer{ display:flex; gap:10px; justify-content:flex-end; padding:12px 16px; border-top:1px solid var(--border) }

    @media (max-width:980px){
      .app{ grid-template-columns:64px 1fr }
      .logo-title, .nav a span{ display:none }
      .nav a{ justify-content:center }
      .grid-2{ grid-template-columns:1fr }
      .container{ max-width:100% }
      .calendar-frame{ height:720px }
    }
  </style>
</head>
<body>
  <div class="app">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="logo">
        <img src="img/logo.png" alt="Logo">
        <div class="logo-title">Portal</div>
      </div>
      <nav class="nav">
        <a href="#" class="active">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
            <line x1="16" y1="2" x2="16" y2="6" stroke-width="2"/>
            <line x1="8" y1="2" x2="8" y2="6" stroke-width="2"/>
            <line x1="3" y1="10" x2="21" y2="10" stroke-width="2"/>
          </svg>
          <span>Escala</span>
        </a>
        <a href="#" id="openProfile">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="7" r="4" stroke-width="2"/>
            <path d="M6 21a6 6 0 0 1 12 0" stroke-width="2"/>
          </svg>
          <span>Perfil</span>
        </a>
      </nav>
    </aside>

    <!-- PRINCIPAL -->
    <section>
      <div class="topbar">
        <div class="brand">
          <img src="img/logo.png" alt="">
          <div>Portal Bombeiro</div>
        </div>
        <div class="profile" id="openProfile2" title="Meus Dados">
          <span>João Silva • Soldado - 1ª Cia</span>
          <div class="avatar">JS</div>
        </div>
      </div>

      <main class="main">
        <div class="container">
          <div class="card">
            <!-- Apenas o iframe; o calendário é carregado do Google -->
            <iframe
              class="calendar-frame"
              src="https://script.google.com/macros/s/AKfycbz9pOlfcq9FMedQnykBvBSQ0EYVXaVa5Gsp4j5tMzaNmBp1EQiQ2dpfDS85qQ2Xy_L3/exec"  
              referrerpolicy="no-referrer"
              loading="lazy"
            ></iframe>
          </div>
        </div>
      </main>
    </section>
  </div>

  <!-- MODAL: MEUS DADOS (UI) -->
  <div class="modal-backdrop" id="modalProfile">
    <div class="modal">
      <header>
        <h3>Meus Dados</h3>
        <button class="btn" id="closeProfile">✕</button>
      </header>
      <div class="content">
        <div class="section">
          <h4>Dados Principais</h4>
          <div class="grid-2">
            <div class="field"><label>Nome Completo</label><input type="text" placeholder="Ex.: ANDREA ZART"></div>
            <div class="field"><label>CPF</label><input type="text" placeholder="000.000.000-00"></div>
          </div>
          <div class="grid-2" style="margin-top:12px">
            <div class="field"><label>Tipo</label>
              <select>
                <option>BC (Voluntário)</option>
                <option>BM</option>
              </select>
            </div>
            <div class="field"><label>Status</label>
              <select><option>Ativo</option><option>Inativo</option></select>
            </div>
          </div>
        </div>

        <div class="section">
          <h4>Endereço (Opcional)</h4>
          <div class="field"><label>Rua/Logradouro</label><input type="text"></div>
          <div class="grid-2" style="margin-top:12px">
            <div class="field"><label>Número</label><input type="text"></div>
            <div class="field"><label>Bairro</label><input type="text"></div>
          </div>
          <div class="grid-2" style="margin-top:12px">
            <div class="field"><label>Cidade</label><input type="text"></div>
            <div class="field"><label>UF</label><input type="text" maxlength="2"></div>
          </div>
          <div class="field" style="margin-top:12px"><label>CEP</label><input type="text" placeholder="00000-000"></div>
        </div>

        <div class="section">
          <h4>Contato (Opcional)</h4>
          <div class="grid-2">
            <div class="field"><label>Telefone Principal</label><input type="text" placeholder="(00) 00000-0000"></div>
            <div class="field"><label>E-mail</label><input type="email" placeholder="nome@exemplo.com"></div>
          </div>
          <div class="grid-2" style="margin-top:12px">
            <div class="field"><label>Contato de Emergência (Nome)</label><input type="text"></div>
            <div class="field"><label>Contato de Emergência (Telefone)</label><input type="text" placeholder="(00) 00000-0000"></div>
          </div>
        </div>

        <div class="section">
          <h4>Uniformes (Opcional)</h4>
          <div class="grid-2">
            <div class="field"><label>Gandola</label><input type="text" placeholder="Ex.: M, G, 42"></div>
            <div class="field"><label>Camiseta</label><input type="text" placeholder="Ex.: P, M, G"></div>
          </div>
          <div class="grid-2" style="margin-top:12px">
            <div class="field"><label>Calça</label><input type="text" placeholder="Ex.: 40, 42, M"></div>
            <div class="field"><label>Calçado (nº)</label><input type="text" placeholder="Ex.: 41, 42"></div>
          </div>
        </div>

        <div class="section">
          <h4>Dados Bancários (Opcional)</h4>
          <div class="field"><label>Informações</label><textarea rows="3" placeholder="Banco, agência, conta, titular, CPF/CNPJ"></textarea></div>
        </div>
      </div>
      <footer>
        <button class="btn" id="saveProfile">Salvar (conectar ao PHP depois)</button>
      </footer>
    </div>
  </div>

  <script>
    // Apenas a lógica do modal permanece (sem FullCalendar local)
    const modal = document.getElementById('modalProfile');
    document.getElementById('openProfile').onclick = () => modal.style.display = 'flex';
    document.getElementById('openProfile2').onclick = () => modal.style.display = 'flex';
    document.getElementById('closeProfile').onclick = () => modal.style.display = 'none';
    document.getElementById('saveProfile').onclick = () => {
      alert('Depois conectamos este formulário ao PHP/MySQL.');
      modal.style.display = 'none';
    };
  </script>
</body>
</html>
