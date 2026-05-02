<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nome']    = $user['nome'];
        $_SESSION['ruolo']   = $user['ruolo'];
        if ($user['ruolo'] == 'staff' || $user['ruolo'] == 'admin') {
            header('Location: staff.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = 'Email o password errati.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login – Da Mario</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header">
    <h1>Pizzeria Da Mario</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="register.php">Registrati</a>
    </nav>
</div>

<div class="container">
    <div class="form-box">
        <h2>Accedi</h2>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Accedi</button>
        </form>
        <p style="margin-top:14px;font-size:.9rem;">Non hai un account? <a href="register.php">Registrati</a></p>
        <div class="success" style="margin-top:16px;font-size:.85rem;">
            <strong>Account demo (password: admin123):</strong><br>
            Admin: admin@damario.it<br>
            Staff: staff@damario.it
        </div>
    </div>
</div>

</body>
</html>
