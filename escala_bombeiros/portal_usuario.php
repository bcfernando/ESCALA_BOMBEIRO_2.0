<?php
// C:\xampp\htdocs\escala_bombeiros\portal_usuario.php
session_start();
if (empty($_SESSION['usuario_id'])) {
  header('Location: /escala_bombeiros/login.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Minha Escala</title>
  <link rel="stylesheet" href="css/portal_usuario.css">
</head>
<body>
  <header class="topbar">
    <h1 class="app-title">Sistema de Escala de Plantões - Desenvolvido por Luiz Fernando Hohn</h1>
    <div class="legend">
      <span><i class="chip chip-i"></i>Integral</span>
      <span><i class="chip chip-d"></i>Diurno</span>
      <span><i class="chip chip-n"></i>Noturno</span>
      <a class="logout" href="login.php?logout=1">Sair</a>
    </div>
  </header>

  <div class="container">
    <section class="card resumo">
      <div class="row-head">
        <h2>Resumo do Mês</h2>
      </div>

      <div class="stats-grid">
        <div class="stat-box">
          <div id="total-earnings" class="stat-value">R$ 0</div>
          <div class="stat-label">Total</div>
        </div>
        <div class="stat-box">
          <div id="total-hours" class="stat-value">0h</div>
          <div class="stat-label">Horas</div>
        </div>
      </div>

      <div class="summary">
        <p>Integral: <span id="sum-integral">0x • R$ 0</span></p>
        <p>Diurno:   <span id="sum-diurno">0x • R$ 0</span></p>
        <p>Noturno:  <span id="sum-noturno">0x • R$ 0</span></p>
      </div>
    </section>

    <section class="card calendario">
      <div class="calendar-header">
        <h2 id="month-label">—</h2>
        <div class="nav-buttons">
          <button id="btn-prev" type="button">‹</button>
          <button id="btn-today" type="button">Hoje</button>
          <button id="btn-next" type="button">›</button>
        </div>
      </div>

      <div class="week-row">
        <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
      </div>
      <div id="calendar-grid" class="calendar-grid"></div>
    </section>
  </div>

  <script src="js/portal_usuario.js"></script>
</body>
</html>
