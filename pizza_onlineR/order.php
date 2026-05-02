<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['ruolo'] != 'cliente') {
    header('Location: index.php');
    exit;
}

$message = '';
$error   = '';

// Annulla ordine (solo se in_attesa)
if (isset($_GET['annulla'])) {
    $id_ord = (int)$_GET['annulla'];
    $chk = $db->prepare("SELECT stato FROM orders WHERE id = ? AND user_id = ?");
    $chk->execute([$id_ord, $_SESSION['user_id']]);
    $ord_chk = $chk->fetch();
    if ($ord_chk && $ord_chk['stato'] == 'in_attesa') {
        $db->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?")->execute([$id_ord, $_SESSION['user_id']]);
        $message = 'Ordine annullato.';
    } else {
        $error = 'Non puoi annullare questo ordine.';
    }
}

// Nuovo ordine
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prodotto  = trim($_POST['prodotto']);
    $quantita  = (int)$_POST['quantita'];
    $indirizzo = trim($_POST['indirizzo']);
    $note      = trim($_POST['note']);

    if (!$prodotto || $quantita < 1 || !$indirizzo) {
        $error = 'Compila tutti i campi obbligatori.';
    } else {
        $stmt = $db->prepare("INSERT INTO orders (user_id, prodotto, quantita, indirizzo, note) VALUES (?,?,?,?,?)");
        if ($stmt->execute([$_SESSION['user_id'], $prodotto, $quantita, $indirizzo, $note])) {
            $message = 'Ordine inviato con successo! Lo stiamo preparando.';
        } else {
            $error = 'Errore durante l\'invio. Riprova.';
        }
    }
}

// Indirizzo utente
$u = $db->prepare("SELECT indirizzo FROM users WHERE id = ?");
$u->execute([$_SESSION['user_id']]);
$user_data = $u->fetch();

// Prodotti disponibili
$products = $db->query("SELECT * FROM products WHERE disponibile = 1 ORDER BY nome");

// Ordini recenti
$stmt2 = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY data_ora DESC LIMIT 10");
$stmt2->execute([$_SESSION['user_id']]);
$orders = $stmt2->fetchAll();

$prod_sel = $_GET['prod'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Ordina – Da Mario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header">
    <h1>Pizzeria Da Mario</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="order.php">Ordina</a>
        <a href="logout.php">Esci</a>
        <span>Ciao, <?= htmlspecialchars($_SESSION['nome']) ?></span>
    </nav>
</div>

<div class="container">
    <h2>Nuovo Ordine</h2>

    <?php if ($message): ?><div class="success"><?= $message ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="error"><?= $error ?></div><?php endif; ?>

    <div class="form-box" style="max-width:520px;">
        <form method="POST">
            <div class="form-group">
                <label>Prodotto *</label>
                <select name="prodotto" required>
                    <?php while ($p = $products->fetch()): ?>
                        <option value="<?= htmlspecialchars($p['nome']) ?>"
                            <?= ($p['nome'] == $prod_sel) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nome']) ?> – €<?= number_format($p['prezzo'], 2, ',', '.') ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantita *</label>
                <input type="number" name="quantita" value="1" min="1" max="20" required>
            </div>
            <div class="form-group">
                <label>Indirizzo di consegna *</label>
                <input type="text" name="indirizzo" value="<?= htmlspecialchars($user_data['indirizzo'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Note (opzionale)</label>
                <textarea name="note" placeholder="Es. citofono rotto, chiamare prima..."></textarea>
            </div>
            <button type="submit" class="btn btn-green">Invia Ordine</button>
        </form>
    </div>

    <h3>I tuoi ordini recenti</h3>
    <?php if (count($orders) > 0): ?>
    <table>
        <tr>
            <th>Data</th>
            <th>Prodotto</th>
            <th>Qt.</th>
            <th>Stato</th>
            <th>Azioni</th>
        </tr>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td><?= substr($o['data_ora'], 0, 16) ?></td>
            <td><?= htmlspecialchars($o['prodotto']) ?></td>
            <td><?= $o['quantita'] ?></td>
            <td class="stato-<?= $o['stato'] ?>"><?= ucfirst(str_replace('_', ' ', $o['stato'])) ?></td>
            <td>
                <?php if ($o['stato'] == 'in_attesa'): ?>
                    <a href="?annulla=<?= $o['id'] ?>" class="btn btn-red btn-sm"
                       onclick="return confirm('Vuoi davvero annullare?')">Annulla</a>
                <?php else: ?>
                    –
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p>Nessun ordine ancora. Ordina qualcosa!</p>
    <?php endif; ?>
</div>

</body>
</html>
