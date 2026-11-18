<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <span class="navbar-brand mb-0 h1">Aplikasi</span>
            <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
        </div>
    </nav>
    <main class="container py-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h3">Halo, <?= htmlspecialchars($_SESSION["name"]) ?>!</h1>
                <p class="text-muted mb-0">Anda berhasil login sebagai <strong><?= htmlspecialchars($_SESSION["username"]) ?></strong>.</p>
            </div>
        </div>
    </main>
</body>
</html>
