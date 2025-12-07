<?php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['user'] ?? '';
    $senha   = $_POST['pass'] ?? '';

    require_once 'includes/db.php';

    if (!$conn) {
        $error = 'Erro ao conectar ao banco de dados.';
    } else {
        $stmt = $conn->prepare("SELECT id, nome, senha_hash, tipo, ativo FROM usuarios WHERE usuario = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if ($row['ativo'] == 1 && password_verify($senha, $row['senha_hash'])) {
                    $_SESSION['usuario_id']   = $row['id'];
                    $_SESSION['usuario_nome'] = $row['nome'];
                    $_SESSION['usuario_tipo'] = $row['tipo'];

                    if ($row['tipo'] === 'admin') {
                        header('Location: /escala_bombeiros/escala.php');        // painel
                    } else {
                        header('Location: /escala_bombeiros/portal_usuario.php'); // portal do usuário
                    }
                    exit;
                } else {
                    $error = 'Usuário ou senha inválidos. Tente novamente.';
                }
            } else {
                $error = 'Usuário ou senha inválidos. Tente novamente.';
            }

            $stmt->close();
        } else {
            $error = 'Erro na consulta ao banco de dados.';
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login • Sistema</title>
    <style>
        :root{
            --bg-start:#0b1220;
            --bg-end:#111827;
            --card:#0f172a80; /* glass */
            --card-solid:#0f172a; /* solid fallback */
            --text:#e5e7eb;
            --muted:#94a3b8;
            --primary:#3b82f6; /* azul */
            --primary-600:#2563eb;
            --accent:#f43f5e; /* botão alternativo */
            --error:#f87171;
            --ring:#60a5fa;
            --shadow: 0 10px 30px rgba(0,0,0,.45);
            --radius: 18px;
        }
        *{box-sizing:border-box}
        html,body{height:100%}
        body{
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            color:var(--text);
            background:
                radial-gradient(1200px 600px at 20% -10%, rgba(59,130,246,.25), transparent 60%),
                radial-gradient(900px 500px at 120% 10%, rgba(244,63,94,.18), transparent 60%),
                linear-gradient(160deg, var(--bg-start), var(--bg-end));
            display:grid;
            place-items:center;
            height:100vh;   /* ocupa toda a tela // centralização vertical */
            padding:0;      /* remove o espaço que empurrava o conteúdo */
        }
        .login-wrap{ 
            width:100%; 
            max-width:420px; 
            transform: translateY(40px); /* desce um pouco o card (ajuste fino) */
        }
        .brand{
            display:flex; align-items:center; gap:12px; margin-bottom:18px; justify-content:center;
            user-select:none;
        }
        .brand .logo{ width:36px; height:36px; display:grid; place-items:center; border-radius:12px; background:linear-gradient(135deg,var(--primary),var(--primary-600)); box-shadow:var(--shadow); }
        .brand h1{ font-size:22px; font-weight:700; letter-spacing:.3px; margin:0; }
        .card{
            background: linear-gradient(to bottom right, rgba(15,23,42,.70), rgba(15,23,42,.55));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(148,163,184,.18);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 28px;
        }
        .hint{ color:var(--muted); font-size:14px; margin:0 0 22px; text-align:center; }
        .field{ display:grid; gap:6px; margin-bottom:16px; }
        label{ font-size:13px; color:var(--muted); }
        .control{ position:relative; }
        .control input{
            width:100%;
            padding:14px 44px 14px 44px;
            border-radius:12px;
            border:1px solid rgba(148,163,184,.25);
            outline:none;
            background: linear-gradient(180deg, rgba(2,6,23,.85), rgba(2,6,23,.65));
            color:var(--text);
            transition: border-color .2s, box-shadow .2s, transform .02s;
        }
        .control input::placeholder{ color:#6b7280 }
        .control input:focus{
            border-color:var(--ring);
            box-shadow:0 0 0 4px rgba(96,165,250,.20);
        }
        .icon{ position:absolute; left:12px; top:50%; transform:translateY(-50%); opacity:.9 }
        .toggle-pass{ position:absolute; right:8px; top:50%; transform:translateY(-50%); border:0; background:transparent; color:#9ca3af; padding:6px 10px; cursor:pointer; border-radius:8px; }
        .toggle-pass:hover{ color:#d1d5db }
        .row{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:2px; margin-bottom:18px; }
        .row .remember{ display:flex; align-items:center; gap:8px; font-size:13px; color:var(--muted) }
        .row a{ color:#c7d2fe; text-decoration:none; font-size:13px; }
        .row a:hover{ text-decoration:underline }
        .btn{
            width:100%;
            border:0; cursor:pointer; padding:14px 16px; border-radius:12px; font-weight:700; letter-spacing:.2px;
            background: linear-gradient(135deg, var(--primary), var(--primary-600)); color:white;
            box-shadow: 0 10px 18px rgba(37,99,235,.25);
            transition: transform .03s ease-in, filter .15s ease;
        }
        .btn:active{ transform: translateY(1px) }
        .btn.secondary{ background: linear-gradient(135deg, var(--accent), #be123c); box-shadow: 0 10px 18px rgba(244,63,94,.25) }
        .divider{ display:flex; align-items:center; gap:10px; margin:18px 0; color:var(--muted); font-size:12px }
        .divider::before, .divider::after{ content:""; height:1px; background: rgba(148,163,184,.25); flex:1 }
        .providers{ display:grid; gap:10px; }
        .provider{ display:flex; align-items:center; justify-content:center; gap:10px; border:1px solid rgba(148,163,184,.25); border-radius:12px; padding:12px; background: rgba(15,23,42,.4); color:var(--text); cursor:pointer }
        .provider:hover{ border-color: rgba(148,163,184,.45) }
        .error{ display:none; background: rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.35); color:#fecaca; padding:10px 12px; border-radius:10px; font-size:13px; margin-bottom:14px }
        footer{ margin-top:18px; text-align:center; color:var(--muted); font-size:11px }
        @media (max-width: 380px){
            .card{ padding:22px }
            .brand h1{ font-size:20px }
        }
    </style>
</head>
<body>
    <main class="login-wrap">
        <div class="brand" aria-label="Identidade do sistema">
            <div class="logo" aria-hidden="true" style="background:none; box-shadow:none;">
                <img src="img/logo.png" alt="Logo" style="width:36px; height:36px; border-radius:12px; display:block;" />
            </div>
            <h1>Bem-vindo</h1>
        </div>

        <section class="card" aria-label="Cartão de login">
            <p class="hint">Acesse sua conta para continuar</p>

            <form id="loginForm" method="post" novalidate>
                <div class="error" id="errorBox" style="<?php echo $error ? 'display:block;' : 'display:none;'; ?>">
                    <?php echo $error ?: ''; ?>
                </div>

                <div class="field">
                    <label for="user">Usuário</label>
                    <div class="control">
                        <span class="icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5Zm0 2c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5Z" fill="#9ca3af"/></svg>
                        </span>
                        <input id="user" name="user" type="text" placeholder="Seu usuário" autocomplete="username" required />
                    </div>
                </div>

                <div class="field">
                    <label for="pass">Senha</label>
                    <div class="control">
                        <span class="icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 8h-1V6a4 4 0 1 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V6a2 2 0 1 1 4 0v2h-4Z" fill="#9ca3af"/></svg>
                        </span>
                        <input id="pass" name="pass" type="password" placeholder="••••••••" autocomplete="current-password" minlength="4" required />
                        <div id="capsLockWarning" style="display:none;color:#f87171;font-size:12px;margin-top:4px;">Caps Lock ativado!</div>
                        <button type="button" class="toggle-pass" aria-label="Mostrar/ocultar senha" title="Mostrar/ocultar senha" onclick="togglePassword()">
                            <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor"/></svg>
                        </button>
                    </div>
                </div>

                <div class="row">
                    <label class="remember"><input id="remember" type="checkbox" /> Manter conectado</label>
                    <a href="#" onclick="alert('Fluxo de recuperação'); return false;">Esqueci minha senha</a>
                </div>

                <button class="btn" type="submit">Entrar</button>
            </form>

            <footer>
                © <span id="year"></span> <strong>Todos os direitos reservados a Luiz Fernando Hohn.</strong>
            </footer>
        </section>
    </main>

    <script>
        const yearEl = document.getElementById('year');
        yearEl.textContent = new Date().getFullYear();

        function togglePassword(){
            const input = document.getElementById('pass');
            const icon = document.getElementById('eyeIcon');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            icon.innerHTML = type === 'password'
                ? '<path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor"/>'
                : '<path d="M2 12s3 7 10 7c2.16 0 4.086-.63 5.72-1.63l2.955 2.955 1.414-1.414L4.222 3.808 2.808 5.222 6.31 8.723C3.976 10.032 2 12 2 12Zm7.567-1.848 1.58 1.58A4 4 0 0 0 12 16a4 4 0 0 0 3.268-1.733l1.534 1.534A7.54 7.54 0 0 1 12 19C5 19 2 12 2 12a13.94 13.94 0 0 1 4.71-4.71l2.857 2.862ZM12 9c.424 0 .824.105 1.174.29l1.535 1.535A2.99 2.99 0 0 1 15 12a3 3 0 0 1-3 3 2.99 2.99 0 0 1-1.175-.29L10.71 12.88A2.99 2.99 0 0 1 9 12a3 3 0 0 1 3-3Z" fill="currentColor"/>';
        }

        // Lembrar usuário e senha (localStorage)
        window.addEventListener('DOMContentLoaded', function() {
            const userInput = document.getElementById('user');
            const passInput = document.getElementById('pass');
            const remember  = document.getElementById('remember');

            if(localStorage.getItem('usuario')) userInput.value = localStorage.getItem('usuario');
            if(localStorage.getItem('senha'))   passInput.value = localStorage.getItem('senha');
            if(localStorage.getItem('lembrar') === 'true') remember.checked = true;

            document.getElementById('loginForm').addEventListener('submit', function() {
                if(remember.checked) {
                    localStorage.setItem('usuario', userInput.value);
                    localStorage.setItem('senha',   passInput.value);
                    localStorage.setItem('lembrar', 'true');
                } else {
                    localStorage.removeItem('usuario');
                    localStorage.removeItem('senha');
                    localStorage.removeItem('lembrar');
                }
            });

            // Aviso Caps Lock
            passInput.addEventListener('keyup', function(e) {
                const caps = e.getModifierState && e.getModifierState('CapsLock');
                document.getElementById('capsLockWarning').style.display = caps ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>
