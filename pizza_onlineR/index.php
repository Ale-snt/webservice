<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Pizzeria Da Mario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header">
    <h1>Pizzeria Da Mario</h1>
    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            <span>Ciao, <?= htmlspecialchars($_SESSION['nome']) ?></span>
            <?php if ($_SESSION['ruolo'] == 'staff' || $_SESSION['ruolo'] == 'admin'): ?>
                <a href="staff.php">Pannello Staff</a>
            <?php endif; ?>
            <?php if ($_SESSION['ruolo'] == 'admin'): ?>
                <a href="admin.php">Admin</a>
            <?php endif; ?>
            <a href="order.php">Ordina</a>
            <a href="logout.php">Esci</a>
        <?php else: ?>
            <a href="login.php">Accedi</a>
            <a href="register.php">Registrati</a>
        <?php endif; ?>
    </nav>
</div>

<div class="container">
    <h2>Benvenuto!</h2>
    <p>Ordina le migliori pizze comodamente da casa. Consegna a domicilio.</p>

    <h3>Il nostro Menu</h3>
    <div class="products-grid">
    <?php
    $stmt = $db->query("SELECT * FROM products WHERE disponibile = 1 ORDER BY nome");
    while ($row = $stmt->fetch()):
    ?>
        <div class="product">
            <h3><?= htmlspecialchars($row['nome']) ?></h3>
            <div class="price">€<?= number_format($row['prezzo'], 2, ',', '.') ?></div>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['ruolo'] == 'cliente'): ?>
                <a href="order.php?prod=<?= urlencode($row['nome']) ?>&prezzo=<?= $row['prezzo'] ?>" class="btn">Ordina</a>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="btn">Accedi per ordinare</a>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
    </div>
</div>

</body>
</html>
