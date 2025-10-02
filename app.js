// Password eye icon logic (login)
const eyeIcon = document.getElementById('toggleEye');
const passwordInput = document.getElementById('login-password');
eyeIcon.addEventListener('click', function() {
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    eyeIcon.src = 'assets/img/password_hide.svg';
    eyeIcon.alt = 'Hide Password';
  } else {
    passwordInput.type = 'password';
    eyeIcon.src = 'assets/img/password_show.svg';
    eyeIcon.alt = 'Show Password';
  }
});

// Password eye icon logic (register modal - main password)
const regEye = document.getElementById('toggleRegEye');
const regPwd = document.getElementById('reg-password');
regEye.addEventListener('click', function() {
  if (regPwd.type === 'password') {
    regPwd.type = 'text';
    regEye.src = 'assets/img/password_hide.svg';
    regEye.alt = 'Hide Password';
  } else {
    regPwd.type = 'password';
    regEye.src = 'assets/img/password_show.svg';
    regEye.alt = 'Show Password';
  }
});
// Password eye icon logic (register modal - confirm password)
const regConfEye = document.getElementById('toggleRegConfirmEye');
const regConf = document.getElementById('reg-confirm');
regConfEye.addEventListener('click', function() {
  if (regConf.type === 'password') {
    regConf.type = 'text';
    regConfEye.src = 'assets/img/password_hide.svg';
    regConfEye.alt = 'Hide Password';
  } else {
    regConf.type = 'password';
    regConfEye.src = 'assets/img/password_show.svg';
    regConfEye.alt = 'Show Password';
  }
});

// Modal logic for show/hide register
const registerModal = document.getElementById('registerModal');
document.getElementById('show-register').onclick = (e) => {
  e.preventDefault();
  registerModal.style.display = 'flex';
};
document.getElementById('closeRegister').onclick = () => registerModal.style.display = 'none';

// 2FA modal logic (similar)
const twoFAModal = document.getElementById('twoFAModal');
if(document.getElementById('close2FA')) {
  document.getElementById('close2FA').onclick = () => twoFAModal.style.display = 'none';
}

// 2FA input auto-move, error shake
const tiles = document.querySelectorAll('#twoFAForm .tiles input');
if(tiles.length) {
  tiles.forEach((tile, idx) => {
    tile.addEventListener('input', e => {
      if (tile.value && idx < tiles.length - 1) tiles[idx + 1].focus();
      if (Array.from(tiles).every(t => t.value)) document.getElementById('twoFAForm').submit();
    });
  });

  document.getElementById('twoFAForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Replace below with actual verification logic using AJAX/PHP
    let wrongCode = false; // Simulate check
    if (wrongCode) {
      document.querySelector('#twoFAModal .modal-content').classList.add('shake');
      setTimeout(() => {
        document.querySelector('#twoFAModal .modal-content').classList.remove('shake');
      }, 550);
      document.getElementById('twoFAStatus').textContent = 'Wrong code! Try again.';
      tiles.forEach(tile => tile.value = '');
      tiles[0].focus();
    } else {
      // Success handler
    }
  });
}

// Google buttons (stub logic)
document.getElementById('googleSignIn').addEventListener('click', function() {
  alert("Google sign-in logic goes here. Only @slssr.edu(.ph) allowed.");
});
document.getElementById('googleReg').addEventListener('click', function() {
  alert("Google register logic goes here. Only @slssr.edu(.ph) allowed.");
});

// Register form validation for domain and pwd match
document.getElementById('registerForm').addEventListener('submit', function(e) {
  e.preventDefault();
  let email = this.email.value;
  if (!email.match(/@slssr\.edu(\.ph)?$/)) {
    document.getElementById('regStatus').textContent = 'Only @slssr.edu(.ph) emails are allowed.';
    return;
  }
  if (this.password.value !== this['reg-confirm'].value) {
    document.getElementById('regStatus').textContent = 'Passwords do not match!';
    return;
  }
  // Proceed to AJAX for backend registration.
  document.getElementById('regStatus').textContent = 'Registering...';
});

// Password toggle using Bootstrap
document.getElementById('toggleEye').addEventListener('click', function(e) {
  e.preventDefault();
  const pwd = document.getElementById('login-password');
  const icon = this.querySelector('img');
  if (pwd.type === "password") {
    pwd.type = "text";
    icon.src = "assets/img/password_hide.svg";
    icon.alt = "Hide";
  } else {
    pwd.type = "password";
    icon.src = "assets/img/password_show.svg";
    icon.alt = "Show";
  }
});
