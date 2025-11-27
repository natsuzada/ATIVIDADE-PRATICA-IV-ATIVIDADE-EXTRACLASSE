<?php

session_start();

require_once 'conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: tarefas.php");
    exit;
}

$email = "";
$errors = [];
$sucesso = "";

if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso') {
    $sucesso = "Cadastro realizado com sucesso! Faça login.";
}

if (isset($_GET['logout']) && $_GET['logout'] === 'sucesso') {
    $sucesso = "Você saiu da sua conta com sucesso.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "E-mail inválido.";
    }

    if (empty($senha)) {
        $errors[] = "A senha não pode estar vazia.";
    }

    if (empty($errors)) {
        $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ?";

        if ($result = execute_query($sql, 's', [$email], $link)) {
            if (mysqli_num_rows($result) == 1) {
                $usuario = mysqli_fetch_assoc($result);

                if (password_verify($senha, $usuario['senha'])) {
                    session_regenerate_id();
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    header("Location: tarefas.php");
                    exit;
                }
            }
            $errors[] = "E-mail ou senha incorretos.";
        } else {
             $errors[] = "Ocorreu um erro na autenticação.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lista de Tarefas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>To-Do WebSite</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php" class="active"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="cadastro.php"><i class="fas fa-user-plus"></i> Cadastro</a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content-wrapper">
        <header class="content-header">
            <h2>Login</h2>
        </header>
        <div class="main-content">
            <div class="container">

                <?php if (!empty($sucesso)): ?>
                    <div class="success-box">
                        <p><?= htmlspecialchars($sucesso) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $erro): ?>
                            <p><?= htmlspecialchars($erro) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

                    <label>Senha:</label>
                    <input type="password" name="senha" required>

                    <button type="submit" class="btn-primary">Entrar</button>
                    <p style="text-align: center; margin-top: 15px;">Não tem conta? <a href="cadastro.php" style="color: var(--azul); text-decoration: none; font-weight: 600;">Cadastre-se</a></p>
                </form>
            </div>
        </div>
        <footer class="main-footer">
            &copy; <?= date('Y') ?> To-Do App. Todos os direitos reservados.
        </footer>

    </div>
</body>
</html>