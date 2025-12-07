<?php
// gera_hash.php
// Coloque em: htdocs/escala_bombeiros/gera_hash.php
// Executar no navegador. Apague após uso.

require_once __DIR__ . '/includes/db.php';

function esc($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome    = trim($_POST['nome'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = $_POST['senha'] ?? '';
    $tipo    = trim($_POST['tipo'] ?? 'bc');
    $ativo   = isset($_POST['ativo']) ? 1 : 0;
    $force   = isset($_POST['force']) ? true : false;

    if ($nome === '' || $usuario === '' || $senha === '') {
        $message = 'Preencha nome, usuário e senha.';
    } else {
        if (!$conn) {
            $message = 'Erro: sem conexão com o banco.';
        } else {
            // verifica existência
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $res = $stmt->get_result();
            $exists = (bool)$res->fetch_assoc();
            $stmt->close();

            $hash = password_hash($senha, PASSWORD_DEFAULT);

            if ($exists && !$force) {
                $message = "Usuário '{$usuario}' já existe. Marque 'Sobrescrever' para atualizar senha/tipo/ativo.";
            } else {
                if ($exists) {
                    $u = $conn->prepare("UPDATE usuarios SET nome = ?, senha_hash = ?, tipo = ?, ativo = ? WHERE usuario = ?");
                    $u->bind_param("sssds", $nome, $hash, $tipo, $ativo, $usuario);
                    $ok = $u->execute();
                    $u->close();
                    $message = $ok ? "Usuário '{$usuario}' atualizado com sucesso." : "Erro ao atualizar: " . $conn->error;
                } else {
                    $i = $conn->prepare("INSERT INTO usuarios (nome, usuario, senha_hash, tipo, ativo) VALUES (?, ?, ?, ?, ?)");
                    $i->bind_param("ssssd", $nome, $usuario, $hash, $tipo, $ativo);
                    $ok = $i->execute();
                    $i->close();
                    $message = $ok ? "Usuário '{$usuario}' criado com sucesso." : "Erro ao inserir: " . $conn->error;
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Gerar hash e criar usuário</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#0f172a;color:#e6eef8;padding:24px}
.card{max-width:720px;margin:24px auto;background:rgba(255,255,255,0.03);padding:18px;border-radius:10px;border:1px solid rgba(255,255,255,0.04)}
label{display:block;margin:8px 0 4px;color:#cbd5e1}
input[type=text], input[type=password], select{width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:rgba(0,0,0,0.25);color:#e6eef8}
.row{display:flex;gap:12px}
.col{flex:1}
button{margin-top:12px;padding:10px 14px;border-radius:8px;border:0;background:#2563eb;color:#fff;cursor:pointer}
.note{margin-top:12px;color:#93c5fd}
.msg{margin-top:12px;padding:10px;border-radius:8px;background:rgba(34,197,94,0.12);color:#d1fae5}
.warn{background:rgba(245,158,11,0.12);color:#fff2d6}
</style>
</head>
<body>
<div class="card">
  <h2>Gerar hash e criar/atualizar usuário</h2>
  <p class="note">Preencha e clique em Criar. Marque "Sobrescrever" para atualizar um usuário existente. Apague este arquivo após o uso.</p>

  <?php if($message): ?>
    <div class="msg"><?= esc($message) ?></div>
  <?php endif; ?>

  <form method="post">
    <label>Nome completo</label>
    <input type="text" name="nome" value="<?= esc($_POST['nome'] ?? '') ?>" required>

    <label>Usuário (login)</label>
    <input type="text" name="usuario" value="<?= esc($_POST['usuario'] ?? '') ?>" required>

    <div class="row">
      <div class="col">
        <label>Senha</label>
        <input type="password" name="senha" value="<?= esc($_POST['senha'] ?? '123') ?>" required>
      </div>
      <div style="width:160px">
        <label>Tipo (role)</label>
        <select name="tipo">
          <option value="bc" <?= (($_POST['tipo'] ?? '')==='bc')?'selected':'' ?>>bc</option>
          <option value="admin" <?= (($_POST['tipo'] ?? '')==='admin')?'selected':'' ?>>admin</option>
        </select>
      </div>
    </div>

    <label><input type="checkbox" name="ativo" <?= isset($_POST['ativo']) ? 'checked' : 'checked' ?>> Ativo</label>
    <label><input type="checkbox" name="force" <?= isset($_POST['force']) ? 'checked' : '' ?>> Sobrescrever se existir</label>

    <button type="submit">Criar / Atualizar usuário</button>
  </form>

  <div class="warn">
    <strong>Segurança:</strong> Apague este arquivo quando terminar. Não deixar disponível em produção.
  </div>
</div>
</body>
</html>
