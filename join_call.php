<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: hospital_admin.php"); exit; }

$call_id = (int)($_GET['id'] ?? 0);
$room = $_GET['room'] ?? '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Join Call (Admin)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { font-family: system-ui, sans-serif; margin:0; padding:16px; }
    video { width: 48%; max-height: 40vh; background:#000; border-radius:8px; }
    .row { display:flex; gap:4%; flex-wrap:wrap; justify-content:center; }
    .actions { margin-top:12px; display:flex; gap:8px; align-items:center; }
    button { padding:10px 16px; border:none; border-radius:6px; cursor:pointer; background:#1976d2; color:#fff; font-size:15px; }
    button:hover { background:#1565c0; }
    #status { font-weight:bold; color:#333; }
  </style>
</head>
<body>
  <h3>Incoming Emergency Call</h3>

  <div class="row">
    <video id="local" autoplay playsinline muted></video>
    <video id="remote" autoplay playsinline></video>
  </div>

  <div class="actions">
    <button id="acceptBtn">Accept</button>
    <button id="endBtn">End</button>
    <span id="status"></span>
  </div>

<script>
(async () => {
  const statusEl = document.getElementById('status');
  const localV = document.getElementById('local');
  const remoteV = document.getElementById('remote');
  const callId = <?= $call_id ?>;
  let pc, lastSignalId = 0, pollTimer;
  let offerPayload = null; // store incoming offer

  function setStatus(s){ statusEl.textContent = s; }

  // get local media
  const stream = await navigator.mediaDevices.getUserMedia({ video:true, audio:true });
  localV.srcObject = stream;

  // setup peer connection
  pc = new RTCPeerConnection({ iceServers:[ {urls:'stun:stun.l.google.com:19302'} ] });
  stream.getTracks().forEach(t => pc.addTrack(t, stream));
  pc.ontrack = e => { remoteV.srcObject = e.streams[0]; };

  pc.onicecandidate = async (e) => {
    if(e.candidate){
      await fetch('send_signal.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          call_id: callId,
          type: 'candidate',
          payload: JSON.stringify(e.candidate)
        })
      });
    }
  };

  // poll for signals
  async function poll(){
    const signals = await fetch(`get_signals.php?call_id=${callId}&after_id=${lastSignalId}`).then(r=>r.json());
    for(const s of signals){
      lastSignalId = s.id;

      if(s.type === 'offer'){
        offerPayload = s.payload;
        setStatus('📩 Incoming call… click Accept');
      } else if(s.type === 'candidate'){
        try { await pc.addIceCandidate(new RTCIceCandidate(JSON.parse(s.payload))); } catch(e){ console.warn(e); }
      } else if(s.type === 'end'){
        setStatus('❌ Call ended by patient');
        cleanup();
      }
    }
  }
  pollTimer = setInterval(poll, 1000);

  // accept button
  async function accept(){
    if(!offerPayload){ setStatus('Waiting for caller offer…'); return; }

    // mark call as accepted
    await fetch('accept_call.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ call_id: callId })
    });

    const offer = JSON.parse(offerPayload);
    await pc.setRemoteDescription(new RTCSessionDescription(offer));

    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);

    await fetch('send_signal.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ call_id: callId, type:'answer', payload: JSON.stringify(answer) })
    });

    setStatus('✅ Connected');
  }

  // cleanup function
  async function cleanup(){
    clearInterval(pollTimer);
    try { pc.close(); } catch {}
    stream.getTracks().forEach(t=>t.stop());
  }

  document.getElementById('acceptBtn').onclick = accept;

  document.getElementById('endBtn').onclick = async ()=>{
    await fetch('end_call.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ call_id: callId }) });
    await fetch('send_signal.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ call_id: callId, type:'end', payload:'{}' }) });
    setStatus('You ended the call');
    cleanup();
  };

})();
</script>
</body>
</html>
