/*
  Frontend helpers + modal UX
  - Manual implementations of modal, 2FA tile input, password visibility toggles
  - Client-side domain checks for Google placeholder sign-in
*/
async function postJSON(url, data){
  const res = await fetch(url, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  return res.json();
}

// Simple DOM helpers
const $ = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));

// Elements
const loginForm = $('#loginForm');
const loginMessage = $('#loginMessage');
const modalOverlay = $('#modalOverlay');
const modal2fa = $('#modal2fa');
const modalRegister = $('#modalRegister');
const openRegisterBtn = $('#openRegister');
const closeRegisterBtn = $('#closeRegister');
const close2faBtn = $('#close2fa');
const twofaTiles = $('#twofaTiles');
const twofaMsg = $('#twofaMsg');
const remember30 = $('#remember30');
const registerFormModal = $('#registerFormModal');
const registerMessage = $('#registerMessage');
const registerResult = $('#registerResult');

// Modal controls (overlay click does NOT close)
function showModal(modal){ modalOverlay.classList.remove('hidden'); modal.classList.remove('hidden'); }
function hideModal(modal){ modalOverlay.classList.add('hidden'); modal.classList.add('hidden'); }
openRegisterBtn.addEventListener('click', e=>{ e.preventDefault(); showModal(modalRegister); });
closeRegisterBtn.addEventListener('click', e=>{ hideModal(modalRegister); });
close2faBtn.addEventListener('click', e=>{ hideModal(modal2fa); });

// password visibility toggles
$$('.pwtoggle').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const inp = btn.parentElement.querySelector('input');
    if(!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁️' : '🙈';
  });
});

// 2FA tiles: create 6 tile elements and manage input
const tiles = [];
for(let i=0;i<6;i++){
  const d = document.createElement('div'); d.className='tile'; d.dataset.idx = i; d.textContent = '';
  twofaTiles.appendChild(d); tiles.push(d);
}
let tileVals = Array(6).fill('');
let tileIndex = 0;

// focus capture: listen for key input globally while modal open
document.addEventListener('keydown', async (ev)=>{
  if(modal2fa.classList.contains('hidden')) return;
  if(ev.key === 'Backspace'){
    if(tileIndex>0){ tileIndex--; tileVals[tileIndex]=''; tiles[tileIndex].textContent=''; }
    ev.preventDefault(); return;
  }
  if(/^[0-9]$/.test(ev.key) && tileIndex < 6){
    tileVals[tileIndex] = ev.key; tiles[tileIndex].textContent = ev.key; tileIndex++;
    // auto submit when full
    if(tileIndex === 6){
      const code = tileVals.join('');
      await submit2fa(code);
    }
  }
});

async function submit2fa(code){
  twofaMsg.textContent = '';
  const remember = remember30.checked;
  const res = await postJSON('api/verify_2fa.php', {code, remember});
  if(res.success){
    // success -> redirect
    window.location = 'dashboard.php';
  } else {
    // shake animation
    twofaTiles.classList.remove('shake');
    void twofaTiles.offsetWidth;
    twofaTiles.classList.add('shake');
    twofaMsg.textContent = res.error || 'Invalid code';
    tileVals = Array(6).fill(''); tileIndex = 0; tiles.forEach(t=>t.textContent='');
  }
}

// Login flow: show 2FA modal instead of prompt
loginForm.addEventListener('submit', async e=>{
  e.preventDefault(); loginMessage.textContent='';
  const fd = new FormData(loginForm);
  const body = {user: fd.get('user'), password: fd.get('password')};
  const res = await postJSON('api/login.php', body);
  if(res.success && res.needs2fa){
    showModal(modal2fa);
  } else if(res.success){
    window.location = 'dashboard.php';
  } else {
    loginMessage.textContent = res.error || 'Login failed';
  }
});

// Register modal flow
registerFormModal.addEventListener('submit', async e=>{
  e.preventDefault(); registerMessage.textContent=''; registerResult.textContent='';
  const fd = new FormData(registerFormModal);
  const body = {username: fd.get('username'), email: fd.get('email'), password: fd.get('password'), role: fd.get('role')};
  const res = await postJSON('api/register.php', body);
  if(res.success){
    registerMessage.style.color = 'green'; registerMessage.textContent = 'Registered. Set up 2FA below.';
    registerResult.textContent = `TOTP secret: ${res.secret}\n\notpauth URL:\n${res.otpauth}`;
    // hide fields (indicate completion)
    registerFormModal.style.display = 'none';
  } else {
    registerMessage.style.color = 'crimson'; registerMessage.textContent = res.error || 'Registration failed';
  }
});

// Google Sign-in placeholders: accept only allowed domain or existing test emails
const allowedGoogleDomain = 'slssr.edu.ph';
function simulateGoogleSignIn(email){
  // client-side check; backend must also enforce
  const domain = email.split('@')[1] || '';
  if(domain.toLowerCase() !== allowedGoogleDomain && !['espino.jamesbryant20+admin@gmail.com','espino.jamesbryant20+teacher@gmail.com','espino.jamesbryant20+students@gmail.com'].includes(email)){
    alert('Google sign-in restricted to ' + allowedGoogleDomain + ' domain');
    return;
  }
  // for demo, just fill the login form and submit
  loginForm.querySelector('[name="user"]').value = email;
  // autopopulate a known password for test accounts is not stored client-side; prompt instead
  const p = prompt('Enter password for ' + email);
  if(!p) return;
  loginForm.querySelector('[name="password"]').value = p;
  loginForm.dispatchEvent(new Event('submit'));
}

$('#googleSignIn').addEventListener('click', ()=>{
  const email = prompt('Enter your Google email (demo)'); if(!email) return; simulateGoogleSignIn(email);
});
$('#googleSignUp').addEventListener('click', ()=>{
  const email = prompt('Enter your Google email (demo)'); if(!email) return; if(!email.endsWith('@'+allowedGoogleDomain)) return alert('Registration requires '+allowedGoogleDomain); registerFormModal.querySelector('[name="email"]').value = email;
});

