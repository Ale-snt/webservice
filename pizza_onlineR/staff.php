<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['ruolo'] != 'staff' && $_SESSION['ruolo'] != 'admin')) {
    header('Location: index.php');
    exit;
}

$message = '';

// Aggiorna stato ordine
if (isset($_GET['update']) && isset($_GET['stato'])) {
    $id    = (int)$_GET['update'];
    $stato = $_GET['stato'];
    $allowed = ['in_attesa', 'in_lavorazione', 'pronto', 'consegnato'];
    if (in_array($stato, $allowed)) {
        $stmt = $db->prepare("UPDATE orders SET stato = ? WHERE id = ?");
        $stmt->execute([$stato, $id]);
        $message = 'Stato aggiornato.';
    }
}

// Filtro stato
$filtro = $_GET['filtro'] ?? 'attivi';
if ($filtro == 'tutti') {
    $orders = $db->query("SELECT o.*, u.nome as cliente FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.data_ora DESC");
} else {
    $orders = $db->query("SELECT o.*, u.nome as cliente FROM orders o JOIN users u ON o.user_id = u.id WHERE o.stato != 'consegnato' ORDER BY o.data_ora DESC");
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Staff – Da Mario</title>
    <link rel="stylesheet" href="style.css">
    <!-- Auto refresh ogni 30 secondi -->
    <meta http-equiv="refresh" content="30">
</head>
<body>

<div class="header">
    <h1>Pizzeria Da Mario – Staff</h1>
    <nav>
        <a href="index.php">Home</a>
        <?php if ($_SESSION['ruolo'] == 'admin'): ?>
            <a href="admin.php">Admin</a>
        <?php endif; ?>
        <a href="logout.php">Esci</a>
        <span>Ciao, <?= htmlspecialchars($_SESSION['nome']) ?></span>
    </nav>
</div>

<div class="container">
    <h2>Gestione Ordini</h2>
    <?php if ($message): ?><div class="success"><?= $message ?></div><?php endif; ?>

    <div style="margin-bottom:16px;display:flex;gap:10px;">
        <a href="?filtro=attivi" class="btn <?= $filtro=='attivi'?'btn-green':'' ?>">Ordini attivi</a>
        <a href="?filtro=tutti"  class="btn <?= $filtro=='tutti' ?'btn-green':'' ?>">Tutti gli ordini</a>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Prodotto</th>
            <th>Qt.</th>
            <th>Indirizzo</th>
            <th>Note</th>
            <th>Data</th>
            <th>Stato</th>
            <th>Aggiorna</th>
        </tr>
        <?php while ($o = $orders->fetch()): ?>
        <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= htmlspecialchars($o['cliente']) ?></td>
            <td><?= htmlspecialchars($o['prodotto']) ?></td>
            <td><?= $o['quantita'] ?></td>
            <td><?= htmlspecialchars($o['indirizzo']) ?></td>
            <td><?= htmlspecialchars($o['note'] ?? '–') ?></td>
            <td><?= substr($o['data_ora'], 0, 16) ?></td>
            <td class="stato-<?= $o['stato'] ?>"><?= ucfirst(str_replace('_', ' ', $o['stato'])) ?></td>
            <td style="white-space:nowrap;">
                <?php if ($o['stato'] == 'in_attesa'): ?>
                    <a href="?update=<?= $o['id'] ?>&stato=in_lavorazione&filtro=<?= $filtro ?>" class="btn btn-blue btn-sm">In lavorazione</a>
                <?php elseif ($o['stato'] == 'in_lavorazione'): ?>
                    <a href="?update=<?= $o['id'] ?>&stato=pronto&filtro=<?= $filtro ?>" class="btn btn-green btn-sm">Pronto</a>
                <?php elseif ($o['stato'] == 'pronto'): ?>
                    <a href="?update=<?= $o['id'] ?>&stato=consegnato&filtro=<?= $filtro ?>" class="btn btn-sm">Consegnato</a>
                <?php else: ?>
                    –
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <p style="margin-top:10px;font-size:.8rem;color:#888;">Pagina si aggiorna automaticamente ogni 30 secondi.</p>
</div>

</body>
</html>
