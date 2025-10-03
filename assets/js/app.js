document.addEventListener('DOMContentLoaded', function() {
  // Helper password toggle
  function addEyeToggle(inputId, btnId) {
    var pw = document.getElementById(inputId);
    var btn = document.getElementById(btnId);
    if (pw && btn) {
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var img = btn.querySelector('img');
        if(pw.type === 'password'){
          pw.type = 'text';
          img.src = 'assets/img/password_hide.svg';
          img.alt = 'Hide';
        } else {
          pw.type = 'password';
          img.src = 'assets/img/password_show.svg';
          img.alt = 'Show';
        }
      });
    }
  }

  addEyeToggle('login-password', 'toggleEye');
  addEyeToggle('reg-password', 'toggleRegEye');
  addEyeToggle('reg-confirm', 'toggleRegConfirmEye');

  // Login form submit
  var loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      var email = this.email.value;
      var password = this.password.value;

      fetch('api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
      }).then(res => res.json())
        .then(data => {
          if (data.success) {
            // Save email for 2FA session
            fetch('api/set_session.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: `email=${encodeURIComponent(email)}`
            }).then(() => show2FA());
          } else {
            alert(data.error || 'Login failed');
          }
        }).catch(() => alert('Login error'));
    });
  }

  // Register modal
  var registerModal = document.getElementById('registerModal');
  var showRegisterBtn = document.getElementById('show-register');
  var closeRegisterBtn = document.getElementById('closeRegister');

  if (showRegisterBtn && registerModal) {
    showRegisterBtn.addEventListener('click', function(e){
      e.preventDefault();
      registerModal.classList.add('show');
    });
  }
  if (closeRegisterBtn && registerModal) {
    closeRegisterBtn.addEventListener('click', function(){
      registerModal.classList.remove('show');
      var regForm = document.getElementById('registerForm');
      var regSuccess = document.getElementById('regSuccessMsg');
      var regBody = document.getElementById('registerModalBody');
      if (regForm) regForm.reset();
      if (regSuccess) regSuccess.classList.add('d-none');
      if (regBody) regBody.classList.remove('d-none');
      var regStatus = document.getElementById('regStatus');
      if (regStatus) regStatus.textContent = '';
    });
  }

  // Register form submit
  var regForm = document.getElementById('registerForm');
  if (regForm) {
    regForm.onsubmit = function(e) {
      e.preventDefault();
      var email = this.email.value;
      var password = this['reg-password'].value;
      var confirm = this['reg-confirm'].value;
      var regStatus = document.getElementById('regStatus');

      if (!/@slssr\.edu(\.ph)?$/.test(email)) {
        if (regStatus) regStatus.textContent = 'Only @slssr.edu(.ph) emails are allowed.';
        return;
      }
      if (password !== confirm) {
        if (regStatus) regStatus.textContent = 'Passwords do not match!';
        return;
      }
      // Use AJAX to register
      var formData = new URLSearchParams(new FormData(this));

      fetch('api/register.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData.toString()
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          var regBody = document.getElementById('registerModalBody');
          var regSuccess = document.getElementById('regSuccessMsg');
          if (regBody && regSuccess) {
            regBody.classList.add('d-none');
            regSuccess.classList.remove('d-none');
            document.getElementById('userSecret').textContent = data.secret || 'Error generating secret';
          }
        } else {
          if (regStatus) regStatus.textContent = data.error || 'Registration failed';
        }
      }).catch(() => {
        if (regStatus) regStatus.textContent = 'Error submitting registration';
      });
    };
  }

  // 2FA Modal logic
  var twoFAModal = document.getElementById('twoFAModal');
  var close2FABtn = document.getElementById('close2FA');
  
  window.show2FA = function() {
    if (twoFAModal) {
      twoFAModal.classList.add('show');
      setTimeout(function() {
        var t = document.querySelector('#faCodeTiles input');
        if (t) t.focus();
      }, 100);
    }
  };

  if (close2FABtn && twoFAModal) {
    close2FABtn.addEventListener('click', function() {
      twoFAModal.classList.remove('show');
      twoFAModal.dataset.shake = '';
      var faForm = document.getElementById('twoFAForm');
      if (faForm) faForm.reset();
    });
  }

  // 2FA tiles logic and submit via AJAX
  var faCodeTilesParent = document.getElementById('faCodeTiles');
  if (faCodeTilesParent) {
    var faCodeTiles = faCodeTilesParent.querySelectorAll('input');
    faCodeTiles.forEach(function(tile, idx) {
      tile.addEventListener('input', function() {
        if(this.value.length === 1 && idx < faCodeTiles.length - 1)
          faCodeTiles[idx+1].focus();
        if(Array.from(faCodeTiles).every(el=>el.value.length === 1)){
          var faForm = document.getElementById('twoFAForm');
          if (faForm) faForm.requestSubmit();
        }
      });
      tile.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && idx > 0) faCodeTiles[idx-1].focus();
      });
    });

    var faForm = document.getElementById('twoFAForm');
    if (faForm) {
      faForm.onsubmit = function(e) {
        e.preventDefault();
        var code = '';
        faCodeTiles.forEach(function(tile){ code += tile.value; });

        fetch('api/verify_2fa.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'code=' + encodeURIComponent(code)
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            window.location.href = data.redirect || 'dashboard.php';
          } else {
            alert(data.error || 'Invalid 2FA code');
            twoFAModal.dataset.shake = '1';
            setTimeout(() => { twoFAModal.dataset.shake = ''; }, 350);
            faCodeTiles.forEach(tile => tile.value = '');
            faCodeTiles[0].focus();
          }
        })
        .catch(() => alert('Error validating 2FA'));
      };
    }
  }

  // Google button stubs
  var googleSignIn = document.getElementById('googleSignIn');
  if (googleSignIn) {
    googleSignIn.addEventListener('click', function() {
      alert("Google sign-in logic goes here. Only @slssr.edu(.ph) allowed.");
    });
  }
  var googleReg = document.getElementById('googleReg');
  if (googleReg) {
    googleReg.addEventListener('click', function() {
      alert("Google register logic goes here. Only @slssr.edu(.ph) allowed.");
    });
  }

  // Register form validation
  var regForm = document.getElementById('registerForm');
  if (regForm) {
    regForm.onsubmit = function(e){
      e.preventDefault();
      var email = this.email.value;
      var password = document.getElementById('reg-password')?.value;
      var confirm = document.getElementById('reg-confirm')?.value;
      var regStatus = document.getElementById('regStatus');
      if (!/@slssr\.edu(\.ph)?$/.test(email)) {
        if (regStatus) regStatus.textContent = 'Only @slssr.edu(.ph) emails are allowed.';
        return;
      }
      if (password !== confirm) {
        if (regStatus) regStatus.textContent = 'Passwords do not match!';
        return;
      }
      var regBody = document.getElementById('registerModalBody');
      var regSuccess = document.getElementById('regSuccessMsg');
      if (regBody && regSuccess) {
        regBody.classList.add('d-none');
        regSuccess.classList.remove('d-none');
        document.getElementById('userSecret').textContent = 'JBSWY3DPEHPK3PXP';
      }
    };
  }
});
