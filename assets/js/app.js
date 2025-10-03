/* app.js (refactored, guarded, 2FA-aware) */
(function () {
  'use strict';

  const $ = (id) => document.getElementById(id);
  const qs = (sel, root = document) => root.querySelector(sel);
  const qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const on = (el, ev, fn, opts) => el && el.addEventListener(ev, fn, opts);
  const show = (el) => { if (el) el.style.display = 'block'; };
  const hide = (el) => { if (el) el.style.display = 'none'; };
  const showFlex = (el) => { if (el) el.style.display = 'flex'; };

  function setStatus(id, msg) {
    const el = $(id);
    if (el) el.textContent = msg;
    else console.log(`[status:${id}] ${msg}`);
  }
  function toForm(data) {
    return Object.entries(data)
      .map(([k, v]) => encodeURIComponent(k) + '=' + encodeURIComponent(v == null ? '' : v))
      .join('&');
  }
  async function post(url, data) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: toForm(data),
      credentials: 'same-origin',
    });
    const text = await res.text();
    try { return JSON.parse(text); } catch { return { ok: false, error: text || 'Invalid server response' }; }
  }
  function openModal(id, mode = 'block') { const el = $(id); if (el) el.style.display = mode; }
  function closeModal(id) { const el = $(id); if (el) el.style.display = 'none'; }

  function addEyeToggle(inputId, btnId, icons = { show: 'assets/img/password_show.svg', hide: 'assets/img/password_hide.svg' }) {
    const input = $(inputId);
    const btn = $(btnId);
    if (!input || !btn) return;
    on(btn, 'click', (e) => {
      e.preventDefault();
      const img = btn.querySelector('img');
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      if (img) { img.src = isHidden ? icons.hide : icons.show; img.alt = isHidden ? 'Hide' : 'Show'; }
    });
  }

  function wire2FATiles(formId, tilesSelector, statusId, onSubmit) {
    const form = $(formId);
    console.log('wire2FATiles called for', formId, form);
    if (!form) return;
    const tiles = qsa(tilesSelector, form);
    if (!tiles.length) return;

    tiles.forEach((tile, idx) => {
      on(tile, 'input', () => {
        tile.value = tile.value.replace(/\D/g, '').slice(0, 1);
        if (tile.value && idx < tiles.length - 1) tiles[idx + 1].focus();
      });
      on(tile, 'keydown', (e) => {
        if (e.key === 'Backspace' && !tile.value && idx > 0) tiles[idx - 1].focus();
      });
    });

    on(form, 'submit', (e) => {
      e.preventDefault();
      const code = tiles.map(t => t.value).join('');
      if (code.length !== tiles.length) { setStatus(statusId, 'Please enter the complete code.'); return; }
      onSubmit(code, { form, tiles });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    console.log('app.js DOMContentLoaded fired');
    addEyeToggle('login-password', 'toggleEye');
    addEyeToggle('reg-password', 'toggleRegEye');
    addEyeToggle('reg-confirm', 'toggleRegConfirmEye');

    const registerModal = $('registerModal');
    const twoFAModal = $('twoFAModal');
    on($('show-register'), 'click', (e) => { e.preventDefault(); if (registerModal) showFlex(registerModal); });
    on($('closeRegister'), 'click', () => closeModal('registerModal'));
    on($('close2FA'), 'click', () => closeModal('twoFAModal'));

    on($('loginForm'), 'submit', async (e) => {
      e.preventDefault();
      const form = e.currentTarget;
      const email = (form.email?.value || '').trim();
      const password = form.password?.value || '';

      setStatus('loginStatus', 'Signing in...');
      try {
        const data = await post('api/login.php', { email, password });
        if (!data || data.ok === false) { setStatus('loginStatus', data?.error || 'Invalid response'); return; }

        // Normalize both possible keys
        const needs2FA = !!(data.twofa_required || data.twofarequired);
        const tempToken = data.temp_token || data.temptoken || '';

        if (needs2FA) {
          if (tempToken) window.sessionStorage.setItem('twofa_token', tempToken);
          if (twoFAModal) openModal('twoFAModal', 'flex');
          else window.location.href = `verify_2fa.php${tempToken ? ('?token=' + encodeURIComponent(tempToken)) : ''}`;
          return;
        }

        setStatus('loginStatus', 'Success. Redirecting...');
        window.location.href = 'dashboard.php';
      } catch (err) {
        console.error(err);
        setStatus('loginStatus', 'Network error. Please try again.');
      }
    });

    wire2FATiles('twoFAForm', '.tiles input', 'twoFAStatus', async (code) => {
      setStatus('twoFAStatus', 'Verifying code...');
      try {
        const tempToken = window.sessionStorage.getItem('twofa_token') || '';
        const data = await post('api/verify_2fa.php', { code, temp_token: tempToken });
        if (!data || data.ok === false) {
          const modalContent = qs('#twoFAModal .modal-content');
          if (modalContent) { modalContent.classList.add('shake'); setTimeout(() => modalContent.classList.remove('shake'), 550); }
          setStatus('twoFAStatus', data?.error || 'Invalid 2FA code. Try again.');
          console.log("Attaching submit handler to twoFAForm");
          qsa('#twoFAForm .tiles input').forEach(t => t.value = '');
          qs('#twoFAForm .tiles input')?.focus();
          return;
        }
        setStatus('twoFAStatus', '2FA success. Redirecting...');
        window.sessionStorage.removeItem('twofa_token');
        window.location.href = 'dashboard.php';
      } catch (err) {
        console.error(err);
        setStatus('twoFAStatus', 'Network error. Please try again.');
      }
    });

    on($('registerForm'), 'submit', async (e) => {
      e.preventDefault();
      const form = e.currentTarget;
      const name = (form.name?.value || '').trim();
      const email = (form.email?.value || '').trim();
      const password = form.password?.value || '';
      const confirm = form['reg-confirm']?.value || '';
      const role = form.role?.value || 'student';

      if (!name || !email || !password || !confirm) { setStatus('regStatus', 'Please fill out all fields.'); return; }
      if (password !== confirm) { setStatus('regStatus', 'Passwords do not match.'); return; }

      setStatus('regStatus', 'Registering...');
      try {
        const data = await post('register.php', { name, email, password, role });
        if (!data || data.ok === false) { setStatus('regStatus', data?.error || 'Registration failed.'); return; }
        setStatus('regStatus', 'Registration successful. You can now sign in.');
        setTimeout(() => closeModal('registerModal'), 800);
      } catch (err) {
        console.error(err);
        setStatus('regStatus', 'Network error. Please try again.');
      }
    });

    on($('googleSignIn'), 'click', () => alert('Google sign-in is not implemented in this demo.'));
    on($('googleReg'), 'click', () => alert('Google registration is not implemented in this demo.'));
  });
})();
