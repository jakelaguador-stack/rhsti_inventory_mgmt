<?php
  ob_start();
  require_once('includes/load.php');
  if($session->isUserLoggedIn(true)) { redirect('home.php', false);}
?>
<?php include_once('layouts/header.php'); ?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>

<style>
  /* Full Screen Reset */
  html, body {
    width: 100vw !important;
    height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important; /* Para walang scrollbars */
    background-color: #0f172a;
  }

  /* Fullscreen Centered Wrapper */
  .login-wrapper {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    width: 100vw;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at 50% 0%, #1e293b 0%, #0f172a 100%);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 9999; /* Sinisiguradong nasa pinakataas na layer */
  }

  /* Background Ambient Glows */
  .login-wrapper::before,
  .login-wrapper::after {
    content: '';
    position: absolute;
    width: 450px;
    height: 450px;
    border-radius: 50%;
    filter: blur(120px);
    opacity: 0.25;
    z-index: 0;
  }

  .login-wrapper::before {
    background: #3b82f6;
    top: -100px;
    left: -100px;
  }

  .login-wrapper::after {
    background: #6366f1;
    bottom: -100px;
    right: -100px;
  }

  /* Main Centered Login Card */
  .login-card {
    position: relative;
    z-index: 1;
    width: 90%;
    max-width: 420px;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
    padding: 40px 36px;
  }

  /* Header Styles */
  .login-header {
    text-align: center;
    margin-bottom: 28px;
  }

  .brand-icon {
    width: 48px;
    height: 48px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
  }

  .login-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin-bottom: 6px;
  }

  .login-header p {
    color: #64748b;
    font-size: 0.875rem;
  }

  /* Form Field Styles */
  .form-group-custom {
    margin-bottom: 20px;
  }

  .form-group-custom label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
    text-align: left;
  }

  .input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
  }

  .input-icon {
    position: absolute;
    left: 14px;
    color: #94a3b8;
    pointer-events: none;
  }

  .input-custom {
    width: 100%;
    padding: 12px 16px 12px 42px;
    font-size: 0.925rem;
    color: #0f172a;
    background-color: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    outline: none;
    transition: all 0.2s ease;
  }

  .input-password {
    padding-right: 44px;
  }

  .input-custom:focus {
    background-color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
  }

  /* Toggle Password Button */
  .toggle-password {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
  }

  .toggle-password:hover {
    color: #475569;
  }

  /* Utilities (Remember me & Forgot Password) */
  .form-utility {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    font-size: 0.85rem;
  }

  .checkbox-label {
    display: flex;
    align-items: center;
    color: #475569;
    cursor: pointer;
    user-select: none;
    font-weight: 500;
  }

  .checkbox-label input {
    margin-right: 8px;
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #2563eb;
  }

  .forgot-link {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
  }

  .forgot-link:hover {
    color: #1d4ed8;
    text-decoration: underline;
  }

  /* Submit Button */
  .btn-submit {
    width: 100%;
    padding: 12px;
    background-color: #2563eb;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.1s ease;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
  }

  .btn-submit:hover {
    background-color: #1d4ed8;
  }

  .btn-submit:active {
    transform: scale(0.98);
  }

  /* Version Tag */
  .version-tag {
    text-align: center;
    margin-top: 24px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #94a3b8;
    letter-spacing: 0.05em;
  }
</style>

<div class="login-wrapper">
  <div class="login-card">
    
    <div class="login-header">
      <div class="brand-icon">
        <i data-lucide="box" style="width: 24px; height: 24px;"></i>
      </div>
      <h1>Inventory Management</h1>
      <p>Sign in to access your dashboard</p>
    </div>

    <?php echo display_msg($msg); ?>

    <form method="post" action="auth.php" autocomplete="on">
      
      <div class="form-group-custom">
        <label for="username">Username</label>
        <div class="input-wrapper">
          <i data-lucide="user" class="input-icon" style="width: 18px; height: 18px;"></i>
          <input id="username" type="text" class="input-custom" name="username" placeholder="Enter your username" required autofocus>
        </div>
      </div>

      <div class="form-group-custom">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <i data-lucide="lock" class="input-icon" style="width: 18px; height: 18px;"></i>
          <input id="password" type="password" class="input-custom input-password" name="password" placeholder="••••••••" required>
          <button type="button" id="togglePasswordBtn" class="toggle-password" title="Show/Hide Password" aria-label="Toggle Password Visibility">
            <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
          </button>
        </div>
      </div>

      <div class="form-utility">
        <label class="checkbox-label">
          <input type="checkbox" name="remember"> Remember me
        </label>
        <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
      </div>

      <button type="submit" class="btn-submit">Sign In</button>
      
    </form>

    <div class="version-tag">VERSION 1.0</div>
    
  </div>
</div>

<script>
  // Initialize Lucide Icons
  lucide.createIcons();

  const passwordInput = document.getElementById('password');
  const toggleBtn = document.getElementById('togglePasswordBtn');

  toggleBtn.addEventListener('click', function() {
    const isPassword = passwordInput.getAttribute('type') === 'password';
    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
    
    if (isPassword) {
      toggleBtn.innerHTML = '<i data-lucide="eye-off" style="width: 18px; height: 18px;"></i>';
    } else {
      toggleBtn.innerHTML = '<i data-lucide="eye" style="width: 18px; height: 18px;"></i>';
    }
    
    lucide.createIcons();
  });
</script>

<?php include_once('layouts/footer.php'); ?>