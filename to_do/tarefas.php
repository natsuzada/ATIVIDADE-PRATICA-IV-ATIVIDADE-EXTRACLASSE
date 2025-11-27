<?php

session_start();

require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];
$errors = [];
$sucesso = [];
$tarefa_edit_id = null;
$tarefa_edit_titulo = '';
$tarefa_edit_descricao = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);

    if (empty($titulo)) {
        $errors[] = "O título da tarefa é obrigatório.";
    } else {
        $sql = "INSERT INTO tarefas (usuario_id, titulo, descricao) VALUES (?, ?, ?)";
        if (execute_query($sql, 'iss', [$usuario_id, $titulo, $descricao], $link)) {
            $sucesso[] = "Tarefa adicionada com sucesso!";
        } else {
            $errors[] = "Erro ao adicionar a tarefa.";
        }
    }
}

if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status']) && isset($_GET['current_status'])) {
    $tarefa_id = (int)$_GET['toggle_status'];
    $current_status = (int)$_GET['current_status'];
    $new_status = $current_status == 0 ? 1 : 0;

    $sql = "UPDATE tarefas SET concluida = ? WHERE id = ? AND usuario_id = ?";
    if (execute_query($sql, 'iii', [$new_status, $tarefa_id, $usuario_id], $link)) {
        header("Location: tarefas.php");
        exit;
    } else {
        $errors[] = "Erro ao atualizar o status da tarefa.";
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $tarefa_id = (int)$_GET['delete'];

    $sql = "DELETE FROM tarefas WHERE id = ? AND usuario_id = ?";
    if (execute_query($sql, 'ii', [$tarefa_id, $usuario_id], $link)) {
        header("Location: tarefas.php");
        exit;
    } else {
        $errors[] = "Erro ao excluir a tarefa.";
    }
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $tarefa_edit_id = (int)$_GET['edit'];

    $sql = "SELECT titulo, descricao FROM tarefas WHERE id = ? AND usuario_id = ?";
    if ($result = execute_query($sql, 'ii', [$tarefa_edit_id, $usuario_id], $link)) {
        if (mysqli_num_rows($result) == 1) {
            $tarefa = mysqli_fetch_assoc($result);
            $tarefa_edit_titulo = htmlspecialchars($tarefa['titulo']);
            $tarefa_edit_descricao = htmlspecialchars($tarefa['descricao']);
        } else {
            $errors[] = "Tarefa não encontrada.";
            $tarefa_edit_id = null;
        }
    } else {
        $errors[] = "Erro ao buscar a tarefa para edição.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $tarefa_id = (int)$_POST['tarefa_id'];
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);

    if (empty($titulo)) {
        $errors[] = "O título da tarefa é obrigatório.";
    } else {
        $sql = "UPDATE tarefas SET titulo = ?, descricao = ? WHERE id = ? AND usuario_id = ?";
        if (execute_query($sql, 'ssii', [$titulo, $descricao, $tarefa_id, $usuario_id], $link)) {
            $sucesso[] = "Tarefa atualizada com sucesso!";
            header("Location: tarefas.php");
            exit;
        } else {
            $errors[] = "Erro ao atualizar a tarefa.";
        }
    }
}

$tarefas = [];
$total_tarefas = 0;
$concluidas = 0;
$pendentes = 0;

$sql = "SELECT id, titulo, descricao, concluida, data_criacao FROM tarefas WHERE usuario_id = ? ORDER BY concluida ASC, data_criacao DESC";
if ($result = execute_query($sql, 'i', [$usuario_id], $link)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tarefas[] = $row;
    }
    $total_tarefas = count($tarefas);
    $concluidas = count(array_filter($tarefas, function($t) { return $t['concluida'] == 1; }));
    $pendentes = $total_tarefas - $concluidas;
} else {
    $errors[] = "Erro ao carregar as tarefas.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Tarefas - To-Do WebSite</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>To-Do Website</h1>
        </div>
        <nav>
            <ul>
                <li><a href="tarefas.php" class="active"><i class="fas fa-list-check"></i> Minhas Tarefas</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
            </ul>
        </nav>
    </div>

    <div class="main-content-wrapper">

        <header class="content-header">
            <h1>Lista de Tarefas de <?= htmlspecialchars($usuario_nome) ?></h1>
            <p style="color: var(--cinza); margin: 0; font-size: 1.1em;">Gerencie suas tarefas diárias com facilidade.</p>
        </header>

        <div class="main-content">
            <div class="container">

                <?php if (!empty($sucesso)): ?>
                    <div class="success-box">
                        <?php foreach ($sucesso as $msg): ?>
                            <p><?= htmlspecialchars($msg) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $erro): ?>
                            <p><?= htmlspecialchars($erro) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h2><?= $tarefa_edit_id ? 'Editar Tarefa' : 'Adicionar Nova Tarefa' ?></h2>
                <form method="POST" action="tarefas.php" class="form-tarefa">
                    <?php if ($tarefa_edit_id): ?>
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="tarefa_id" value="<?= $tarefa_edit_id ?>">
                        <input type="hidden" name="tarefa_edit_original" value="<?= $tarefa_edit_id ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add">
                    <?php endif; ?>

                    <label for="titulo">Título:</label>
                    <input type="text" id="titulo" name="titulo" value="<?= $tarefa_edit_titulo ?>" required maxlength="100">

                    <label for="descricao">Descrição (Opcional):</label>
                    <textarea id="descricao" name="descricao" rows="3" maxlength="500"><?= $tarefa_edit_descricao ?></textarea>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="submit" class="btn-primary"><?= $tarefa_edit_id ? 'Salvar Edição' : 'Adicionar' ?></button>
                        <?php if ($tarefa_edit_id): ?>
                            <a href="tarefas.php" class="btn-secondary" style="text-decoration: none; padding: 12px 20px;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if ($total_tarefas > 0): ?>
                    <div class="grafico-wrapper">
                        <h3>Seu Progresso</h3>
                        <canvas id="tarefasChart"></canvas>
                        <p>
                            Total: <strong style="color: var(--azul);"><?= $total_tarefas ?></strong> tarefas.
                            Concluídas: <strong style="color: var(--concluido);"><?= $concluidas ?></strong>.
                            Pendentes: <strong style="color: var(--pendentes);"><?= $pendentes ?></strong>.
                        </p>
                    </div>
                <?php endif; ?>

                <h2>Suas Tarefas (<?= $total_tarefas ?>)</h2>
                <?php if (empty($tarefas)): ?>
                    <p >Você não tem nenhuma tarefa. Adicione uma acima!</p>
                <?php else: ?>
                    <ul class="tarefa-lista container">
                        <?php foreach ($tarefas as $tarefa): ?>
                            <li class="tarefa-item <?= $tarefa['concluida'] ? 'concluida' : 'pendente' ?>">
                                <div class="tarefa-info">
                                    <p class="tarefa-titulo"><?= htmlspecialchars($tarefa['titulo']) ?></p>
                                    <?php if (!empty($tarefa['descricao'])): ?>
                                        <p class="tarefa-descricao"><?= htmlspecialchars($tarefa['descricao']) ?></p>
                                    <?php endif; ?>
                                    <p class="tarefa-data">Criada em: <?= date('d/m/Y H:i', strtotime($tarefa['data_criacao'])) ?></p>
                                </div>
                                <div class="tarefa-acoes">
                                    <a href="tarefas.php?toggle_status=<?= $tarefa['id'] ?>&current_status=<?= $tarefa['concluida'] ?>"
                                       class="btn-status <?= $tarefa['concluida'] ? 'btn-incomplete' : 'btn-complete' ?>"
                                       title="<?= $tarefa['concluida'] ? 'Marcar como Pendente' : 'Marcar como Concluída' ?>">
                                        <i class="fas <?= $tarefa['concluida'] ? 'fa-undo' : 'fa-check' ?>"></i>
                                    </a>
                                    <a href="tarefas.php?edit=<?= $tarefa['id'] ?>" class="btn-edit" title="Editar Tarefa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="tarefas.php?delete=<?= $tarefa['id'] ?>" class="btn-delete"
                                       onclick="return confirm('Tem certeza que deseja excluir esta tarefa?');"
                                       title="Excluir Tarefa">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <footer class="main-footer">
            &copy; <?= date('Y') ?> To-Do App. Todos os direitos reservados.
        </footer>

    </div>

    <script>
        <?php if ($total_tarefas > 0): ?>
            const concluídas = <?= $concluidas ?>;
            const pendentes = <?= $pendentes ?>;
            const total = concluídas + pendentes;
            const ctx = document.getElementById('tarefasChart').getContext('2d');
            new Chart(ctx, {
            type: 'doughnut',
            data: {
            labels: ['Concluídas', 'Pendentes'],
            datasets: [{
            label: 'Status das Tarefas',
            data: [concluídas, pendentes],
            backgroundColor: [ '#4CAF50', '#ff0000ff' ],
            hoverOffset: 8,
            borderWidth: 2
            }]
        },
            options: {
            responsive: true,
            cutout: '75%',
            plugins: {
            legend: {
            position: 'bottom',
            },
            tooltip: {
            callbacks: {
            label: function(context) {
            const value = context.parsed;
            const percentage = ((value / total) * 100).toFixed(1) + '%';
            return context.label + ': ' + value + ' (' + percentage + ')';
                        }
                    }
                }
            }
        }
    });

        if (window.location.search.includes('edit=')) {
            const form = document.querySelector('.form-tarefa');
            if (form) {
                form.scrollIntoView({ behavior: 'smooth' });
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>