<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>SLSSR — Login</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body class="page-login">
  <div class="bg-layer">
    <!-- Background image and footer layering -->
    <img src="images/loginpage_bg.png" alt="background" class="bg-img" />
    <img src="images/footer.png" alt="footer" class="bg-footer" />
  </div>

  <main class="login-wrapper">
    <div class="panel">
      <div class="panel-inner">
        <h2 class="welcome">Welcome back</h2>
        <p class="sub">Sign in to continue to SLSSR services</p>

        <form id="loginForm" class="form compact">
          <label class="field">Username or Email
            <input name="user" autocomplete="username" required />
          </label>

          <label class="field">Password
            <div class="pwwrap">
              <input name="password" type="password" autocomplete="current-password" required />
              <button type="button" class="pwtoggle" aria-label="Show password">Show</button>
            </div>
          </label>

          <label class="keep"><input type="checkbox" name="keep" /> Keep me logged in</label>

          <button type="submit" class="btn primary full">Log in</button>

          <div class="or"><span>or</span></div>

          <button id="googleSignIn" type="button" class="btn google full">
            <span class="google-logo" aria-hidden="true">
              <!-- svg kept inline -->
              <svg viewBox="0 0 533.5 544.3" xmlns="http://www.w3.org/2000/svg"><path fill="#4285F4" d="M533.5 278.4c0-18.5-1.6-37.1-4.9-55.3H272.1v104.8h147.1c-6.3 34.1-25.1 63-53.6 82.1v68.2h86.5c50.6-46.6 81.4-115.1 81.4-199.8z"/><path fill="#34A853" d="M272.1 544.3c72.9 0 134-24.3 178.7-66.1l-86.5-68.2c-24.2 16.3-55 25.9-92.2 25.9-70.9 0-131-47.9-152.4-112.2H30.9v70.6C75.8 486.7 168.3 544.3 272.1 544.3z"/><path fill="#FBBC05" d="M119.7 324.7c-10.4-30.8-10.4-64 0-94.8V159.3H30.9c-45.6 89.9-45.6 196.8 0 286.7l88.8-70.6z"/><path fill="#EA4335" d="M272.1 109.1c39.6-.6 77 14 105.6 40.6l79.1-79.1C404.5 24.3 343.4 0 272.1 0 168.3 0 75.8 57.6 30.9 159.3l88.8 70.6C141.1 157 201.2 109.1 272.1 109.1z"/></svg>
            </span>
            <span>Continue with Google</span>
          </button>

          <div class="links bottom">
            <a href="#" id="forgotPwd">Forgot password?</a>
            <a href="#" id="openRegister">Register</a>
          </div>
        </form>

        <div id="loginMessage" class="msg"></div>
      </div>
    </div>
  </main>

  <!-- Modal overlays (non-dismissible by clicking overlay) -->
  <div id="modalOverlay" class="overlay hidden"></div>

  <!-- 2FA Modal -->
  <div id="modal2fa" class="modal hidden" role="dialog" aria-modal="true">
    <button class="modal-close" id="close2fa">✕</button>
    <h3>Two-Factor Authentication</h3>
    <p>Enter the 6-digit code from your authenticator app.</p>
    <div id="twofaTiles" class="totp-tiles" aria-label="2FA code input"></div>
    <label class="remember"><input type="checkbox" id="remember30"/> Remember me for 30 days</label>
    <div id="twofaMsg" class="msg"></div>
  </div>

  <!-- Register Modal -->
  <div id="modalRegister" class="modal hidden" role="dialog" aria-modal="true">
    <button class="modal-close" id="closeRegister">✕</button>
    <h3>Register</h3>
    <form id="registerFormModal" class="form">
      <label class="field">First and Last name
        <input name="username" required />
      </label>
      <label class="field">Birthday
        <input name="birthday" type="date" />
      </label>
      <label class="field">Email
        <input name="email" type="email" required />
      </label>
      <label class="field">Password
        <div class="pwwrap">
          <input name="password" type="password" required />
          <button type="button" class="pwtoggle">👁️</button>
        </div>
      </label>
      <label class="field">Confirm Password
        <div class="pwwrap">
          <input name="password_confirm" type="password" required />
          <button type="button" class="pwtoggle">👁️</button>
        </div>
      </label>
      <label class="field">Role
        <select name="role">
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
          <option value="staff">Staff</option>
        </select>
      </label>
      <div class="controls">
        <button id="googleSignUp" type="button" class="btn google">Sign up with Google</button>
        <button type="submit" class="btn primary">Register</button>
      </div>
    </form>
    <div id="registerMessage" class="msg"></div>
    <div id="registerResult" class="mono"></div>
  </div>

  <script src="assets/js/app.js"></script>
</body>
</html>
