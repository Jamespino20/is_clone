<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>St. Luke School of San Rafael - Login</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container-fluid min-vh-100 d-flex flex-column justify-content-between p-0">

    <div class="row flex-grow-1 g-0">
      <!-- Left (school image) -->
      <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-success p-0">
        <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="background: url('assets/img/school-image.png') center center / cover no-repeat;">
          <div class="school-logo">
            <img src="assets/img/school-logo.png" alt="School Logo" class="img-fluid">>
          </div>
        </div>
      </div>

      <!-- Right (login box) -->
      <div class="col-lg-6 d-flex align-items-center justify-content-center bg-white p-0">
        <div class="w-100" style="max-width: 420px; margin: 0 auto;">
          <h2 class="text-success fw-bold mb-2 mt-4 text-center" style="font-family: 'Baskerville', serif;">Welcome back!</h2>
          <div class="mb-3 text-center">
            <small>Don't have an account? <a href="#" id="show-register">Sign up!</a></small>
          </div>
          <form id="loginForm" class="shadow rounded p-4 bg-light">
            <button type="button" class="btn btn-outline-secondary w-100 mb-3 d-flex align-items-center justify-content-center" id="googleSignIn">
              <img src="assets/img/google.svg" class="me-2" style="height: 1.2em;" alt="Google"> Continue with Google
            </button>
            <div class="text-center my-2 small text-muted">or continue with username/email</div>
            <div class="mb-2">
              <label class="form-label mb-1">Username or email address</label>
              <input type="text" name="email" class="form-control" required>
            </div>
            <div class="mb-2">
              <label class="form-label mb-1">Password</label>
              <div class="input-group">
                <input type="password" id="login-password" name="password" class="form-control" required autocomplete="current-password">
                <button class="btn btn-outline-secondary" type="button" id="toggleEye" tabindex="-1">
                  <img src="assets/img/password_show.svg" alt="Show Password" style="width:20px; height:20px;">
                </button>
              </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <a href="#" id="forgot-pass" class="small mt-1">Forgot your password?</a>
              <label class="small"><input type="checkbox" name="keepLoggedIn" class="form-check-input me-1"> Keep me logged in</label>
            </div>
            <button type="submit" class="btn btn-success w-100 mt-1">Sign in</button>
            <div class="status mt-3" id="loginStatus"></div>
          </form>
        </div>
      </div>
    </div> <!-- end main row -->

    <!-- Footer, now scrollable/always visible at the bottom -->
<footer class="custom-footer mt-4">
  <!-- Yellow liner (always spans full width) -->
  <div class="footer-liner">
    <svg viewBox="0 0 2048 50" width="100%" height="30" preserveAspectRatio="none">
      <rect x="90" y="22" width="1868" height="6" fill="#f7e24b"/>
      <polygon points="0,25 90,22 90,28" fill="#f7e24b"/>
      <polygon points="2048,25 1958,22 1958,28" fill="#f7e24b"/>
      <polygon points="1024,20 1032,25 1016,25" fill="#f7e24b"/>
      <polygon points="1024,37 1032,31 1016,31" fill="#f7e24b"/>
    </svg>
  </div>
  
  <!-- Footer content: image left, text right on desktop -->
  <div class="container-fluid">
    <div class="row align-items-center py-3">
      <!-- Image column (hidden on very small screens) -->
  <div class="footer-stack">
    <img src="assets/img/footer.png" class="footer-logo" alt="School Logo" />
    <div class="footer-info">
      St. Luke's School of San Rafael • Sampaloc, San Rafael, Bulacan<br>
      Email: info@slssr.edu.ph • © 2025 St. Luke's School
    </div>
  </div>
      </div>
    </div>
  </div>
</footer>

<!-- Register Modal -->
<div id="registerModal" class="modal-backdrop">
  <div class="modal-dialog">
    <div class="modal-content">
      <button class="modal-close" id="closeRegister">&times;</button>
      <div class="modal-header">
        <h5 class="modal-title">Create your account</h5>
      </div>
      <div class="modal-body" id="registerModalBody">
        <button class="btn-google w-100 mb-2" id="registerWithGoogle">
          <img src="assets/img/google.svg" class="me-2" style="height:1.1em;"> Sign up with Google
        </button>
        <hr class="mb-2 mt-2"/>
<form id="registerForm" autocomplete="off">
  <div class="mb-2">
    <input type="text" placeholder="First Name" name="first" required>
  </div>
  <div class="mb-2">
    <input type="text" placeholder="Last Name" name="last" required>
  </div>
  <div class="mb-2">
    <input type="date" placeholder="Birthday" name="bday" required>
  </div>
  <div class="mb-2">
    <input type="email" placeholder="Email (@slssr.edu.ph)" pattern="^[^@\\s]+@slssr\\.edu\\.ph$" name="email" required>
  </div>
  <div class="mb-2">
    <select name="role" required>
      <option value="" disabled selected>Select your role</option>
      <option value="Student">Student</option>
      <option value="Faculty">Faculty</option>
      <option value="Staff">Staff</option>
      <option value="Administrator">Administrator</option>
    </select>
  </div>
  <div class="mb-2 input-wrapper">
    <input type="password" placeholder="Password" id="reg-password" required>
    <button type="button" class="eye-toggle" id="toggleRegEye">
      <img src="assets/img/password_show.svg" alt="Show">
    </button>
  </div>
  <div class="mb-2 input-wrapper">
    <input type="password" placeholder="Confirm Password" id="reg-confirm" required>
    <button type="button" class="eye-toggle" id="toggleRegConfirmEye">
      <img src="assets/img/password_show.svg" alt="Show">
    </button>
  </div>
  <button class="btn-register w-100 mt-2" type="submit" id="registerBtn">Register</button>
</form>
      </div>
      <div class="modal-body d-none" id="regSuccessMsg">
        <p>Registration successful! Here’s your secret key for Google Auth:</p>
        <div class="secret-key" id="userSecret"></div>
        <p class="mt-2 small">Your TOTP secret can also be found in your student profile.</p>
      </div>
    </div>
  </div>
</div>

<!-- 2FA Modal -->
<div id="twoFAModal" class="modal-backdrop">
  <div class="modal-dialog modal-small">
    <div class="modal-content">
      <button class="modal-close" id="close2FA">&times;</button>
      <div class="modal-header">
        <h5 class="modal-title">Enter your code</h5>
      </div>
      <form class="modal-body" id="twoFAForm" autocomplete="off">
        <div class="code-grid" id="faCodeTiles">
          <input inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
          <input inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
          <input inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
          <input inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
          <input inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
          <input inputmode="numeric" maxlength="1" pattern="[0-9]*" required>
        </div>
        <label class="remember-me"><input type="checkbox" id="faRemember"> Remember me for 30 days</label>
        <button type="submit" class="btn-2fa-submit mt-2">Validate</button>
      </form>
    </div>
  </div>
</div>
  <!-- Bootstrap JS (optional but handy for modals/popups) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>

</html>
