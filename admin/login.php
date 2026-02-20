<?php
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once '../db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username']  = $admin['username'];
            $_SESSION['admin_id']        = $admin['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login | Amore Academy</title>
  <link rel="stylesheet" href="../1HCI.CSS"/>
  <link rel="icon" type="image/png" href="../icon.png"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet"/>
  <style>
    body {
      font-family: var(--font-body);
      background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 60%, var(--color-primary-light) 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      position: relative;
      overflow: hidden;
    }
    body::before {
      content: '';
      position: absolute; inset: 0;
      background: url('../hci_bg.jpg') center/cover no-repeat;
      opacity: .12;
    }
    .login-wrap {
      position: relative; z-index: 2;
      width: 100%; max-width: 440px;
    }
    /* Logo above card */
    .login-logo {
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .login-logo img { width: 90px; filter: drop-shadow(0 4px 16px rgba(0,0,0,.4)); }
    .login-logo p {
      color: rgba(255,255,255,.75);
      font-size: .82rem;
      margin-top: .5rem;
      font-style: italic;
      font-family: var(--font-body);
    }

    .login-card {
      background: var(--color-white);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      overflow: hidden;
    }
    .login-card-header {
      background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary));
      padding: 2rem 2rem 1.75rem;
      text-align: center;
      color: var(--color-white);
    }
    .shield {
      width: 60px; height: 60px;
      background: rgba(251,133,0,.2);
      border: 2px solid rgba(251,133,0,.4);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem;
      margin: 0 auto 1rem;
    }
    .login-card-header h1 {
      font-family: var(--font-display);
      font-size: 1.6rem; font-weight: 700;
      color: var(--color-white);
      margin-bottom: .25rem;
    }
    .login-card-header p { font-size: .82rem; color: rgba(255,255,255,.65); margin: 0; }

    .login-card-body { padding: 2rem; }

    .alert-error {
      background: #fee2e2; color: #991b1b;
      border-left: 4px solid #ef4444;
      padding: .875rem 1rem;
      border-radius: var(--radius-md);
      font-size: .88rem;
      font-weight: 500;
      margin-bottom: 1.25rem;
    }

    .form-group { margin-bottom: 1.125rem; }
    .form-label {
      display: block;
      font-size: .75rem; font-weight: 700;
      color: var(--color-text-medium);
      text-transform: uppercase; letter-spacing: .5px;
      margin-bottom: .45rem;
    }
    .form-control {
      width: 100%;
      padding: .75rem 1rem;
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-md);
      font-size: .92rem;
      font-family: var(--font-body);
      color: var(--color-text-dark);
      background: var(--color-background);
      outline: none;
      transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
    }
    .form-control:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px rgba(10,36,99,.1);
      background: var(--color-white);
    }

    .btn-login {
      width: 100%;
      padding: .875rem;
      background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary));
      color: var(--color-white);
      border: none;
      border-radius: var(--radius-lg);
      font-size: .95rem; font-weight: 700;
      font-family: var(--font-body);
      cursor: pointer;
      transition: var(--transition-normal);
      margin-top: .5rem;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(10,36,99,.3);
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 1.25rem;
      font-size: .83rem;
      color: var(--color-text-light);
      text-decoration: none;
      transition: color var(--transition-fast);
    }
    .back-link:hover { color: var(--color-primary); }

    .hint-box {
      background: var(--color-background);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-md);
      padding: .875rem 1rem;
      font-size: .78rem;
      color: var(--color-text-light);
      text-align: center;
      margin-top: 1.25rem;
    }
    .hint-box code {
      background: var(--color-border);
      padding: 2px 6px;
      border-radius: 4px;
      font-size: .8rem;
    }
  </style>
</head>
<body>
<div class="login-wrap">
  <div class="login-logo">
    <img src="../Amore_Academy_Logo.png" alt="Amore Academy Logo"/>
    <p>Administration Portal</p>
  </div>

  <div class="login-card">
    <div class="login-card-header">
      <div class="shield">🛡️</div>
      <h1>Admin Login</h1>
      <p>Amore Academy — Secured Access</p>
    </div>
    <div class="login-card-body">

      <?php if ($error): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control"
                 placeholder="Enter admin username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 autocomplete="username" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control"
                 placeholder="Enter your password"
                 autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn-login">🔐 Sign In to Dashboard</button>
      </form>

      <a href="../schedule.php" class="back-link">← Back to Schedule Page</a>

      <div class="hint-box">
        Default: <code>admin</code> / <code>Admin@2025</code><br>
        <small>Please change your password after first login.</small>
      </div>
    </div>
  </div>
</div>
</body>
</html>