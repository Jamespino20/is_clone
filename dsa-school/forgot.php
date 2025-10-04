<?php
declare(strict_types=1);
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>St. Luke School of San Rafael - Forgot Password</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" type="image/png" href="assets/img/school-logo.ico">
</head>
<body class="bg-light">
  <div class="container" style="max-width:480px;margin:40px auto;background:#fff;border-radius:14px;box-shadow:0 8px 40px rgba(0,0,0,.08);padding:18px 16px;">
    
    <a href="index.php">
      <button type="button" id="closeForgot" aria-label="Close">×</button>
    </a>

    <h2 class="text-success" style="text-align:center;font-family:'Baskerville',serif">Reset your password</h2>
    <p class="small" style="text-align:center;color:#3a4a3f">Enter your account email. If 2FA is enabled, you'll be asked for your 6‑digit code before resetting.</p>

    <form id="forgotForm">
      <label class="form-label">Email</label>
      <input type="email" name="email" required>
      <div id="forgotStatus" class="status" style="min-height:18px;margin:8px 0"></div>
      <button type="submit" class="btn-register" style="width:100%">Continue</button>
    </form>
  </div>

  <div id="twoFAModal" class="modal" style="display:none;">
    <div class="modal-content">
      <button type="button" id="close2FA" aria-label="Close">×</button>
      <h2>Enter 2FA Code</h2>
      <p>Please enter the 6-digit code from your authenticator app:</p>
      <form id="twoFAForm" method="post">
        <div class="tiles">
          <input type="text" maxlength="1" inputmode="numeric">
          <input type="text" maxlength="1" inputmode="numeric">
          <input type="text" maxlength="1" inputmode="numeric">
          <input type="text" maxlength="1" inputmode="numeric">
          <input type="text" maxlength="1" inputmode="numeric">
          <input type="text" maxlength="1" inputmode="numeric">
        </div>
        <div id="twoFAStatus" class="status"></div>
        <div class="modal-actions">
          <button type="submit" class="btn-2fa-submit">Verify</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  (function(){
    const $=s=>document.querySelector(s); const qsa=s=>Array.from(document.querySelectorAll(s));
    function setMsg(id, msg){ const el=document.getElementById(id); if(el) el.textContent=msg; }
    function toForm(o){ return Object.entries(o).map(([k,v])=>encodeURIComponent(k)+"="+encodeURIComponent(v??'')).join('&'); }
    async function post(url,data){ const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:toForm(data)}); try{return await r.json();}catch{return {ok:false,error:'Invalid response'}} }

    const forgotForm = $('#forgotForm');
    const modal = document.getElementById('twoFAModal');
    const closeBtn = document.getElementById('close2FA');
    closeBtn.addEventListener('click',()=>{ modal.style.display='none'; setMsg('forgotStatus','2FA cancelled.'); });

    forgotForm.addEventListener('submit', async (e)=>{
      e.preventDefault(); setMsg('forgotStatus','Checking account...');
      const email = new FormData(forgotForm).get('email');
      const res = await post('api/forgot_start.php',{ email });
      if(!res.ok){ setMsg('forgotStatus', res.error || 'Account not found'); return; }
      if(res.twofa_required){ modal.style.display='flex'; const first = document.querySelector('#twoFAForm .tiles input'); if(first) first.focus(); }
      else { window.location = 'reset.php?email='+encodeURIComponent(email); }
    });

    // 2FA tiles behavior
    (function(){ const inputs=qsa('#twoFAForm .tiles input');
      inputs.forEach((inp,i)=>{
        inp.addEventListener('input',()=>{ inp.value=inp.value.replace(/\D/g,'').slice(0,1); if(inp.value && i<inputs.length-1) inputs[i+1].focus(); });
        inp.addEventListener('keydown',e=>{ if(e.key==='Backspace' && !inp.value && i>0) inputs[i-1].focus(); });
      });
      document.getElementById('twoFAForm').addEventListener('submit', async (e)=>{
        e.preventDefault(); const code=inputs.map(x=>x.value).join('');
        const email=new FormData(forgotForm).get('email');
        setMsg('twoFAStatus','Verifying code...');
        const res=await post('api/verify_2fa.php',{ code });
        if(!res.ok){ setMsg('twoFAStatus', res.error||'Invalid code'); inputs.forEach(x=>x.value=''); inputs[0].focus(); return; }
        window.location='reset.php?email='+encodeURIComponent(email);
      });
    })();
  })();
  </script>
</body>
</html>


