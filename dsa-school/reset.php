<?php
declare(strict_types=1);
$email = isset($_GET['email']) ? trim((string)$_GET['email']) : '';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password — SLSSR</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
  <div class="container" style="max-width:480px;margin:40px auto;background:#fff;border-radius:14px;box-shadow:0 8px 40px rgba(0,0,0,.08);padding:18px 16px;">
    <h2 class="text-success" style="text-align:center;font-family:'Baskerville',serif">Create a new password</h2>
    <form id="resetForm">
      <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
      <label class="form-label">New password</label>
      <div class="input-wrapper">
        <input type="password" id="npw" name="password" required>
        <button type="button" class="eye-toggle" id="npwEye"><img src="assets/img/password_show.svg" alt="Show"></button>
      </div>
      <label class="form-label">Confirm password</label>
      <div class="input-wrapper">
        <input type="password" id="cpw" name="confirm" required>
        <button type="button" class="eye-toggle" id="cpwEye"><img src="assets/img/password_show.svg" alt="Show"></button>
      </div>
      <div id="resetStatus" class="status" style="min-height:18px;margin:8px 0"></div>
      <button type="submit" class="btn-register" style="width:100%">Update password</button>
    </form>
  </div>

  <script>
    (function(){
      const $=s=>document.querySelector(s);
      function setMsg(id,msg){ const el=document.getElementById(id); if(el) el.textContent=msg; }
      function toForm(o){ return Object.entries(o).map(([k,v])=>encodeURIComponent(k)+"="+encodeURIComponent(v??'' )).join('&'); }
      async function post(url,data){ const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:toForm(data)}); try{return await r.json();}catch{return {ok:false,error:'Invalid response'}} }

      const form = document.getElementById('resetForm');
      document.getElementById('npwEye').addEventListener('click',()=>{ const i=$('#npw'); const img=$('#npwEye img'); const show=i.type==='password'; i.type=show?'text':'password'; img.alt=show?'Hide':'Show'; });
      document.getElementById('cpwEye').addEventListener('click',()=>{ const i=$('#cpw'); const img=$('#cpwEye img'); const show=i.type==='password'; i.type=show?'text':'password'; img.alt=show?'Hide':'Show'; });
      form.addEventListener('submit', async (e)=>{
        e.preventDefault(); const fd=new FormData(form); const email=fd.get('email'); const pw=fd.get('password'); const c=fd.get('confirm');
        if(!pw||pw!==c){ setMsg('resetStatus','Passwords do not match.'); return; }
        setMsg('resetStatus','Updating...'); const res=await post('api/reset_password.php',{ email, password: pw });
        if(!res.ok){ setMsg('resetStatus', res.error || 'Failed to update'); return; }
        setMsg('resetStatus','Password updated. Returning to login...'); setTimeout(()=>{ window.location='index.php'; }, 800);
      });
    })();
  </script>
</body>
</html>


