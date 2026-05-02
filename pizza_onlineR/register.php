<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome      = trim($_POST['nome']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $indirizzo = trim($_POST['indirizzo']);

    if (!$nome || !$email || !$password || !$indirizzo) {
        $error = 'Compila tutti i campi.';
    } elseif (strlen($password) < 6) {
        $error = 'La password deve avere almeno 6 caratteri.';
    } else {
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Email gia registrata.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (nome, email, password, indirizzo) VALUES (?,?,?,?)");
            if ($stmt->execute([$nome, $email, $hash, $indirizzo])) {
                $success = 'Registrazione completata! <a href="login.php">Vai al login</a>';
            } else {
                $error = 'Errore durante la registrazione.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione – Da Mario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header">
    <h1>Pizzeria Da Mario</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Accedi</a>
    </nav>
</div>

<div class="container">
    <div class="form-box">
        <h2>Registrazione</h2>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Nome e Cognome</label>
                <input type="text" name="nome" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password (min. 6 caratteri)</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Indirizzo di consegna</label>
                <input type="text" name="indirizzo" placeholder="Via, numero civico, citta" required>
            </div>
            <button type="submit" class="btn">Registrati</button>
        </form>
        <p style="margin-top:14px;font-size:.9rem;">Hai gia un account? <a href="login.php">Accedi</a></p>
    </div>
</div>

</body>
</html>
