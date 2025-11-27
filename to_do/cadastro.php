<?php
session_start();

require_once 'conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: tarefas.php");
    exit;
}

$errors = [];
$nome = $email = $dataNascimento = $genero = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST['nome']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    $confirmarSenha = $_POST['confirmarSenha'];

    if (empty($nome) || empty($email) || empty($senha) || empty($confirmarSenha)) {
        $errors[] = "Todos os campos são obrigatórios.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "E-mail inválido.";
    }

    if (strlen($senha) < 6 || !preg_match('/[A-Z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
        $errors[] = "A senha deve ter no mínimo 6 caracteres, incluindo 1 letra maiúscula e 1 número.";
    }

    if ($senha !== $confirmarSenha) {
        $errors[] = "As senhas não coincidem.";
    }

    if (empty($errors)) {
        $sql_check = "SELECT id FROM usuarios WHERE email = ?";
        $result_check = execute_query($sql_check, 's', [$email], $link);

        if (mysqli_num_rows($result_check) > 0) {
            $errors[] = "O e-mail informado já está em uso.";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql_insert = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";

            if (execute_query($sql_insert, 'sss', [$nome, $email, $senha_hash], $link)) {
                header("Location: index.php?cadastro=sucesso");
                exit;
            } else {
                $errors[] = "Erro ao tentar cadastrar o usuário.";
            }
        }
    }
}
$nome = htmlspecialchars($nome);
$email = htmlspecialchars($email);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Lista de Tarefas</title>
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
                <li><a href="index.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="cadastro.php" class="active"><i class="fas fa-user-plus"></i> Cadastro</a></li>
            </ul>
        </nav>
    </div>

    <div class="main-content-wrapper">

        <header class="content-header">
            <h2>Cadastro</h2>
        </header>

        <div class="main-content">
            <div class="container">

                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $erro): ?>
                            <p><?= htmlspecialchars($erro) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">

                    <label>Nome completo:</label>
                    <input type="text" name="nome" value="<?= $nome ?>" required>

                    <label>Email:</label>
                    <input type="email" name="email" value="<?= $email ?>" required>

                    <label>Senha:</label>
                    <input type="password" name="senha" required>

                    <label>Confirmar Senha:</label>
                    <input type="password" name="confirmarSenha" required>

                    <button type="submit" class="btn-primary">Cadastrar</button>
                    <p style="text-align: center; margin-top: 15px;">Já tem conta? <a href="index.php" style="color: var(--azul); text-decoration: none; font-weight: 600;">Faça Login</a></p>
                </form>
            </div>
        </div>

        <footer class="main-footer">
            &copy; <?= date('Y') ?> To-Do App. Todos os direitos reservados.
        </footer>

        </div>
    </body>
</html>