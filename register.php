<?php
require_once __DIR__ . "/connection.php";

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "";

    if ($name === "") {
        $errors[] = "Nama lengkap wajib diisi.";
    }
    if ($username === "" || !preg_match("/^[A-Za-z0-9_]{3,50}$/", $username)) {
        $errors[] = "Username 3-50 karakter dan hanya boleh berisi huruf, angka, atau underscore.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email tidak valid.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Konfirmasi password tidak cocok.";
    }
    if (!in_array($role, ["karyawan", "atasan"], true)) {
        $errors[] = "Pilih role Karyawan atau Atasan.";
    }

    if (!$errors) {
        $conn = connection();
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Username atau email sudah digunakan.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $name, $username, $email, $hashedPassword, $role);

            if ($insert->execute()) {
                $success = "Registrasi berhasil. Silakan login.";
                $_POST = [];
            } else {
                $errors[] = "Registrasi gagal. Silakan coba lagi.";
            }
        }

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
    <title>Registrasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-4">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 mb-2 text-center">Buat Akun</h1>
                        <p class="text-muted text-center mb-4">Daftar untuk mulai menggunakan aplikasi.</p>

                        <?php if ($errors): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <div><?= htmlspecialchars($error) ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>

                        <form method="post" action="register.php">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama lengkap</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST["name"] ?? "") ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($_POST["username"] ?? "") ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Daftar sebagai</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Pilih role</option>
                                    <option value="karyawan" <?= ($_POST["role"] ?? "") === "karyawan" ? "selected" : "" ?>>Karyawan</option>
                                    <option value="atasan" <?= ($_POST["role"] ?? "") === "atasan" ? "selected" : "" ?>>Atasan/Admin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Konfirmasi password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Daftar</button>
                        </form>

                        <p class="text-center mt-4 mb-0">
                            Sudah punya akun? <a href="login.php">Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>