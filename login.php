<?php
session_start();
require_once __DIR__ . "/connection.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login === "" || $password === "") {
        $error = "Username/email dan password wajib diisi.";
    } else {
        $conn = connection();
        $stmt = $conn->prepare("SELECT id, name, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            header("Location: index.php");
            exit;
        }

        $error = "Username/email atau password salah.";
        $stmt->close();
        $conn->close();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-12 col-sm-9 col-md-6 col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 mb-2 text-center">Login</h1>
                        <p class="text-muted text-center mb-4">Masuk ke akun Anda.</p>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="post" action="login.php">
                            <div class="mb-3">
                                <label for="login" class="form-label">Username atau Email</label>
                                <input type="text" class="form-control" id="login" name="login" value="<?= htmlspecialchars($_POST["login"] ?? "") ?>" required autofocus>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Masuk</button>
                        </form>

                        <p class="text-center mt-4 mb-0">
                            Belum punya akun? <a href="register.php">Daftar</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>