<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login Dashboard Kunjungan</title>
    <link rel="icon" type="image/png" href="admin/dist/assets/img/logo.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #74ebd5, #acb6e5);
            font-family: 'Arial', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .title::after {
            content: "";
            display: block;
            width: 60px;
            height: 3px;
            background-color: white;
            margin: 5px auto 0;
            border-radius: 2px;
        }

        .login-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
        }

        .login-container img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 15px;
        }

        .form-control {
            padding-left: 40px;
        }

        .input-group-text {
            background: none;
            border-right: none;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #007bff;
        }

        .btn-login {
            width: 100%;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-login:hover {
            background-color: #0056b3;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <!-- Judul di atas box login -->
    <h2 class="title">Selamat Datang di Dashboard Kunjungan Pasien</h2>

    <div class="login-container">
        <!-- Logo -->
        <img src="admin/dist/assets/img/logo.png" alt="User Icon">

        <h5 class="text-muted">Silahkan Login</h5>

        <!-- Form Login -->
        <form method="POST" action="check_auth.php">
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fa fa-user"></i></span>
                <input name="user" type="text" class="form-control" placeholder="Username" required>
            </div>
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                <input name="pass" type="password" class="form-control" placeholder="Password" required>
            </div>

            <button type="submit" class="btn btn-login">Login</button>

            <!-- Pesan Error -->
            <?php if (isset($error)) { ?>
                <p class="text-danger mt-2"><?= $error ?></p>
            <?php } ?>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
