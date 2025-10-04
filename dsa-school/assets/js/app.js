/* app.js (refactored, guarded, 2FA-aware) */
(function () {
  "use strict";

  const $ = (id) => document.getElementById(id);
  const qs = (sel, root = document) => root.querySelector(sel);
  const qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const on = (el, ev, fn, opts) => el && el.addEventListener(ev, fn, opts);
  const show = (el) => {
    if (el) el.style.display = "block";
  };
  const hide = (el) => {
    if (el) el.style.display = "none";
  };
  const showFlex = (el) => {
    if (el) el.style.display = "flex";
  };

  function setStatus(id, msg, type = "info") {
    const el = $(id);
    if (el) {
      el.textContent = msg;
      el.className = `status ${type}`;
    } else {
      console.log(`[status:${id}] ${msg}`);
    }
  }
  function toForm(data) {
    return Object.entries(data)
      .map(
        ([k, v]) =>
          encodeURIComponent(k) + "=" + encodeURIComponent(v == null ? "" : v)
      )
      .join("&");
  }
  async function post(url, data) {
    const res = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
      },
      body: toForm(data),
      credentials: "same-origin",
    });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      return { ok: false, error: text || "Invalid server response" };
    }
  }
  function openModal(id, mode = "flex") {
    const el = $(id);
    if (el) el.style.display = mode;
  }
  function closeModal(id) {
    const el = $(id);
    if (el) el.style.display = "none";
  }

  function showTOTPSecret(secret, email) {
    // Hide the form and show the success message
    const form = $("registerForm");
    const successMsg = $("regSuccessMsg");
    const userSecret = $("userSecret");

    if (form) form.style.display = "none";
    if (successMsg) successMsg.classList.remove("d-none");
    if (userSecret) {
      userSecret.innerHTML = `
        <div class="secret-display">
          <div class="secret-code">
            <strong>Manual Entry Code:</strong><br>
            <code class="secret-text">${secret}</code>
            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="navigator.clipboard.writeText('${secret}')">Copy</button>
          </div>
          <div class="qr-code mt-3">
            <strong>QR Code:</strong><br>
            <div id="qrcode"></div>
          </div>
          <div class="instructions mt-3">
            <small class="text-muted">
              Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.) or manually enter the code above.
            </small>
          </div>
        </div>
      `;

      // Generate QR code
      generateQRCode(secret, email);
    }
  }

  function generateQRCode(secret, email) {
    // Create QR code data in the standard TOTP format
    const issuer = "St. Luke's School of San Rafael";
    const account = email;
    const qrData = `otpauth://totp/${encodeURIComponent(
      issuer
    )}:${encodeURIComponent(
      account
    )}?secret=${secret}&issuer=${encodeURIComponent(issuer)}`;

    const qrContainer = document.getElementById("qrcode");
    if (qrContainer && typeof qrcode !== "undefined") {
      try {
        // Clear any existing content
        qrContainer.innerHTML = "";

        // Generate QR code using qrcode-generator library
        const qr = qrcode(0, "M");
        qr.addData(qrData);
        qr.make();

        // Create canvas element
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        const size = 200;
        const moduleCount = qr.getModuleCount();
        const cellSize = Math.floor(size / moduleCount);
        const actualSize = cellSize * moduleCount;

        canvas.width = actualSize;
        canvas.height = actualSize;

        // Draw QR code
        for (let row = 0; row < moduleCount; row++) {
          for (let col = 0; col < moduleCount; col++) {
            ctx.fillStyle = qr.isDark(row, col) ? "#000000" : "#FFFFFF";
            ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
          }
        }

        qrContainer.appendChild(canvas);
      } catch (error) {
        console.error("QR Code generation error:", error);
        // Fallback to text representation
        qrContainer.innerHTML = `
          <div class="qr-placeholder border p-3 text-center">
            <div class="mb-2">QR Code for Authenticator App</div>
            <div class="small text-muted">Secret: ${secret}</div>
            <div class="small text-muted">Account: ${email}</div>
            <div class="small text-muted">Issuer: St. Luke's School of San Rafael</div>
          </div>
        `;
      }
    } else {
      // Fallback if qrcode library not loaded
      qrContainer.innerHTML = `
        <div class="qr-placeholder border p-3 text-center">
          <div class="mb-2">QR Code for Authenticator App</div>
          <div class="small text-muted">Secret: ${secret}</div>
          <div class="small text-muted">Account: ${email}</div>
          <div class="small text-muted">Issuer: St. Luke's School of San Rafael</div>
        </div>
      `;
    }
  }

  function addEyeToggle(
    inputId,
    btnId,
    icons = {
      show: "assets/img/password_show.svg",
      hide: "assets/img/password_hide.svg",
    }
  ) {
    const input = $(inputId);
    const btn = $(btnId);
    if (!input || !btn) return;
    on(btn, "click", (e) => {
      e.preventDefault();
      const img = btn.querySelector("img");
      const isHidden = input.type === "password";
      input.type = isHidden ? "text" : "password";
      if (img) {
        img.src = isHidden ? icons.hide : icons.show;
        img.alt = isHidden ? "Hide" : "Show";
      }
    });
  }

  function wire2FATiles(formId, tilesSelector, statusId, onSubmit) {
    const form = $(formId);
    if (!form) return;
    const tiles = qsa(tilesSelector, form);
    if (!tiles.length) return;
    tiles.forEach((tile, idx) => {
      on(tile, "input", () => {
        tile.value = tile.value.replace(/\D/g, "").slice(0, 1);
        if (tile.value && idx < tiles.length - 1) tiles[idx + 1].focus();
      });
      on(tile, "keydown", (e) => {
        if (e.key === "Backspace" && !tile.value && idx > 0)
          tiles[idx - 1].focus();
      });
    });
    on(form, "submit", (e) => {
      e.preventDefault();
      const code = tiles.map((t) => t.value).join("");
      if (code.length !== tiles.length) {
        setStatus(statusId, "Please enter the complete code.");
        return;
      }
      onSubmit(code, { form, tiles });
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    addEyeToggle("login-password", "toggleEye");
    addEyeToggle("reg-password", "toggleRegEye");
    addEyeToggle("reg-confirm", "toggleRegConfirmEye");

    const registerModal = $("registerModal");
    const twoFAModal = $("twoFAModal");
    on($("show-register"), "click", (e) => {
      e.preventDefault();
      if (registerModal) showFlex(registerModal);
    });
    on($("closeRegister"), "click", () => closeModal("registerModal"));
    on($("close2FA"), "click", () => {
      closeModal("twoFAModal");
      setStatus("loginStatus", "2FA cancelled.");
    });

    on($("loginForm"), "submit", async (e) => {
      e.preventDefault();
      const form = e.currentTarget;
      const email = (form.email?.value || "").trim();
      const password = form.password?.value || "";
      setStatus("loginStatus", "Signing in...");
      try {
        const data = await post("api/login.php", { email, password });
        if (!data || data.ok === false) {
          setStatus("loginStatus", data?.error || "Invalid response");
          return;
        }
        const needs2FA = !!data.twofa_required;
        if (needs2FA) {
          if (twoFAModal) openModal("twoFAModal", "flex");
          const first = qs("#twoFAForm .tiles input");
          if (first) first.focus();
          return;
        }
        setStatus("loginStatus", "Success. Redirecting...");
        window.location.href = "dashboard.php";
      } catch (err) {
        console.error(err);
        setStatus("loginStatus", "Network error. Please try again.");
      }
    });

    wire2FATiles("twoFAForm", ".tiles input", "twoFAStatus", async (code) => {
      setStatus("twoFAStatus", "Verifying code...");
      try {
        const data = await post("api/verify_2fa.php", { code });
        if (!data || data.ok === false) {
          const modalContent = qs("#twoFAModal .modal-content");
          if (modalContent) {
            modalContent.classList.add("shake");
            setTimeout(() => modalContent.classList.remove("shake"), 550);
          }
          setStatus(
            "twoFAStatus",
            data?.error || "Invalid 2FA code. Try again."
          );
          qsa("#twoFAForm .tiles input").forEach((t) => (t.value = ""));
          qs("#twoFAForm .tiles input")?.focus();
          return;
        }
        setStatus("twoFAStatus", "2FA success. Redirecting...");
        window.location.href = "dashboard.php";
      } catch (err) {
        console.error(err);
        setStatus("twoFAStatus", "Network error. Please try again.");
      }
    });

    on($("registerForm"), "submit", async (e) => {
      e.preventDefault();
      const form = e.currentTarget;
      const first = (form.first?.value || "").trim();
      const last = (form.last?.value || "").trim();
      const name = `${first} ${last}`.trim();
      const email = (form.email?.value || "").trim();
      const password = $("reg-password")?.value || "";
      const confirm = $("reg-confirm")?.value || "";
      const role = form.role?.value || "student";

      if (!first || !last || !email || !password || !confirm) {
        setStatus("regStatus", "Please fill out all fields.", "error");
        return;
      }
      if (password !== confirm) {
        setStatus("regStatus", "Passwords do not match.", "error");
        return;
      }
      setStatus("regStatus", "Registering...", "info");
      try {
        const data = await post("api/register.php", {
          name,
          email,
          password,
          role,
        });
        if (!data || data.ok === false) {
          setStatus(
            "regStatus",
            data?.error || "Incomplete or incorrect credentials. Try again.",
            "error"
          );
          return;
        }

        // Show TOTP secret and QR code
        if (data.totp_secret) {
          showTOTPSecret(data.totp_secret, email);
        } else {
          setStatus(
            "regStatus",
            "Registration successful. You can now sign in.",
            "success"
          );
          setTimeout(() => closeModal("registerModal"), 800);
        }
      } catch (err) {
        console.error(err);
        setStatus("regStatus", "Network error. Please try again.", "error");
      }
    });

    on($("googleSignIn"), "click", () =>
      alert(
        "Google sign-in is not available. Please use the email/password form."
      )
    );
    on($("registerWithGoogle"), "click", () =>
      alert(
        "Google sign-in is not available. Please use the registration form."
      )
    );
  });
})();
