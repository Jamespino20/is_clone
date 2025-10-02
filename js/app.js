document.addEventListener('DOMContentLoaded', function() {
  // Helper for password visibility
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
  addEyeToggle('login-password', 'toggleEye');              // Main login
  addEyeToggle('reg-password', 'toggleRegEye');            // Register password
  addEyeToggle('reg-confirm', 'toggleRegConfirmEye');      // Register confirm

  // Register modal show/hide logic
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
      // Optional: Reset modal form on close
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

  // 2FA Modal logic
  function show2FA() {
    var twoFAModal = document.getElementById('twoFAModal');
    var close2FABtn = document.getElementById('close2FA');
    if (twoFAModal) {
      twoFAModal.classList.add('show');
      setTimeout(function(){
        var t = document.querySelector('#faCodeTiles input');
        if (t) t.focus();
      },100);
    }
  }
  if (close2FABtn && twoFAModal) {
    close2FABtn.addEventListener('click', function(){
      twoFAModal.classList.remove('show');
      twoFAModal.dataset.shake = '';
      var faForm = document.getElementById('twoFAForm');
      if (faForm) faForm.reset();
    });
  }

  // 2FA input logic
  var faCodeTilesParent = document.getElementById('faCodeTiles');
  if (faCodeTilesParent) {
    var faCodeTiles = faCodeTilesParent.querySelectorAll('input');
    faCodeTiles.forEach(function(tile, idx) {
      tile.addEventListener('input', function() {
        if(this.value.length === 1 && idx < faCodeTiles.length - 1)
          faCodeTiles[idx+1].focus();
        if(Array.from(faCodeTiles).every(function(el){ return el.value.length === 1; })){
          var faForm = document.getElementById('twoFAForm');
          if (faForm) faForm.requestSubmit();
        }
      });
      tile.addEventListener('keydown', function(e){
        if(e.key==='Backspace' && !this.value && idx > 0)
          faCodeTiles[idx-1].focus();
      });
    });
    // 2FA form validation and feedback
    var faForm = document.getElementById('twoFAForm');
    if (faForm) {
      faForm.onsubmit = function(e){
        e.preventDefault();
        var code = '';
        faCodeTiles.forEach(function(tile){ code += tile.value; });
        // Dummy logic, replace with your actual check!
        if(code === '123456'){
          alert('2FA success!');
          twoFAModal.classList.remove('show');
          this.reset();
          faCodeTiles[0].focus();
        } else {
          twoFAModal.dataset.shake = '1';
          setTimeout(function(){ twoFAModal.dataset.shake = ''; }, 350);
          faCodeTiles.forEach(function(tile){ tile.value = ''; });
          faCodeTiles[0].focus();
        }
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
      // Successful registration, simulate showing secret
      var regBody = document.getElementById('registerModalBody');
      var regSuccess = document.getElementById('regSuccessMsg');
      if (regBody && regSuccess) {
        regBody.classList.add('d-none');
        regSuccess.classList.remove('d-none');
        document.getElementById('userSecret').textContent = 'JBSWY3DPEHPK3PXP'; // Example
      }
    };
  }
});
