<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['ruolo'] != 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$error   = '';

// Aggiungi prodotto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $nome   = trim($_POST['nome']);
    $prezzo = (float)$_POST['prezzo'];
    if ($nome && $prezzo > 0) {
        $db->prepare("INSERT INTO products (nome, prezzo) VALUES (?,?)")->execute([$nome, $prezzo]);
        $message = 'Prodotto aggiunto.';
    } else {
        $error = 'Inserisci nome e prezzo validi.';
    }
}

// Toggle disponibilita prodotto
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $db->prepare("SELECT disponibile FROM products WHERE id = ?");
    $cur->execute([$id]);
    $row = $cur->fetch();
    $nuovo = $row['disponibile'] ? 0 : 1;
    $db->prepare("UPDATE products SET disponibile = ? WHERE id = ?")->execute([$nuovo, $id]);
    $message = 'Disponibilita aggiornata.';
}

// Elimina prodotto
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    $message = 'Prodotto eliminato.';
}

// Cambia ruolo utente
if (isset($_GET['set_ruolo']) && isset($_GET['uid'])) {
    $uid   = (int)$_GET['uid'];
    $ruolo = $_GET['set_ruolo'];
    $allowed_ruoli = ['cliente', 'staff', 'admin'];
    if (in_array($ruolo, $allowed_ruoli) && $uid != $_SESSION['user_id']) {
        $db->prepare("UPDATE users SET ruolo = ? WHERE id = ?")->execute([$ruolo, $uid]);
        $message = 'Ruolo aggiornato.';
    }
}

$products = $db->query("SELECT * FROM products ORDER BY nome");
$users    = $db->query("SELECT * FROM users ORDER BY ruolo, nome");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Admin – Da Mario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header">
    <h1>Pizzeria Da Mario – Admin</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="staff.php">Pannello Staff</a>
        <a href="logout.php">Esci</a>
        <span>Ciao, <?= htmlspecialchars($_SESSION['nome']) ?></span>
    </nav>
</div>

<div class="container">
    <?php if ($message): ?><div class="success"><?= $message ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="error"><?= $error ?></div><?php endif; ?>

    <!-- ---- GESTIONE MENU ---- -->
    <h2>Gestione Menu</h2>

    <div class="form-box" style="max-width:400px;margin-bottom:24px;">
        <h3 style="margin-bottom:14px;">Aggiungi prodotto</h3>
        <form method="POST">
            <div class="form-group">
                <label>Nome prodotto</label>
                <input type="text" name="nome" required>
            </div>
            <div class="form-group">
                <label>Prezzo (€)</label>
                <input type="number" step="0.01" min="0.01" name="prezzo" required>
            </div>
            <button type="submit" name="add_product" class="btn btn-green">Aggiungi</button>
        </form>
    </div>

    <table>
        <tr>
            <th>Nome</th>
            <th>Prezzo</th>
            <th>Disponibile</th>
            <th>Azioni</th>
        </tr>
        <?php while ($p = $products->fetch()): ?>
        <tr>
            <td><?= htmlspecialchars($p['nome']) ?></td>
            <td>€<?= number_format($p['prezzo'], 2, ',', '.') ?></td>
            <td><?= $p['disponibile'] ? 'Si' : 'No' ?></td>
            <td>
                <a href="?toggle=<?= $p['id'] ?>" class="btn btn-blue btn-sm">
                    <?= $p['disponibile'] ? 'Disabilita' : 'Abilita' ?>
                </a>
                <a href="?delete=<?= $p['id'] ?>" class="btn btn-red btn-sm"
                   onclick="return confirm('Eliminare questo prodotto?')">Elimina</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- ---- GESTIONE UTENTI ---- -->
    <h2 style="margin-top:36px;">Gestione Utenti</h2>
    <table>
        <tr>
            <th>Nome</th>
            <th>Email</th>
            <th>Ruolo attuale</th>
            <th>Cambia ruolo</th>
        </tr>
        <?php while ($u = $users->fetch()): ?>
        <tr>
            <td><?= htmlspecialchars($u['nome']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= $u['ruolo'] ?></td>
            <td>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <?php if ($u['ruolo'] != 'cliente'): ?>
                        <a href="?set_ruolo=cliente&uid=<?= $u['id'] ?>" class="btn btn-sm">Retrocedi a Cliente</a>
                    <?php endif; ?>
                    <?php if ($u['ruolo'] != 'staff'): ?>
                        <a href="?set_ruolo=staff&uid=<?= $u['id'] ?>" class="btn btn-blue btn-sm">Promuovi a Staff</a>
                    <?php endif; ?>
                    <?php if ($u['ruolo'] != 'admin'): ?>
                        <a href="?set_ruolo=admin&uid=<?= $u['id'] ?>" class="btn btn-green btn-sm">Promuovi ad Admin</a>
                    <?php endif; ?>
                <?php else: ?>
                    (sei tu)
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
