<?php
session_start();
require 'config.php';
if (!isset($_SESSION['patient_id'])) { header("Location: patient_login.php"); exit; }

$patient_id = $_SESSION['patient_id'];
$selected_hospital_id = (int)($_GET['hospital_id'] ?? 0);
$selected_hospital_name = '';

// Fetch hospitals
$hospital_list = [];
$stmt = $conn->prepare("SELECT id, name, hospital_name FROM users WHERE role='hospital_admin' AND is_active=1");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $hospital_list[] = $row;
    if ($selected_hospital_id === (int)$row['id']) $selected_hospital_name = $row['hospital_name'];
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Emergency Video Call</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:system-ui,sans-serif;margin:0;padding:16px;background:#f5f5f5;}
h3{margin-bottom:12px;}
video{width:48%;max-height:40vh;background:#000;border-radius:8px;}
.row{display:flex;gap:4%;flex-wrap:wrap;justify-content:center;}
.actions{margin-top:16px;display:flex;gap:12px;align-items:center;}
#status{font-weight:bold;color:#333;}
button{padding:10px 16px;border:none;border-radius:6px;cursor:pointer;background:#c62828;color:#fff;font-size:15px;}
button:hover{background:#b71c1c;}
a.hospital-btn{display:block;padding:10px;background:#1976d2;color:#fff;text-decoration:none;border-radius:6px;margin-bottom:6px;}
a.hospital-btn:hover{background:#1565c0;}
</style>
</head>
<body>

<?php if($selected_hospital_id===0): ?>
<h3>Select a hospital to start an emergency call</h3>
<?php foreach($hospital_list as $h): ?>
<a class="hospital-btn" href="?hospital_id=<?= $h['id'] ?>">
<?= htmlspecialchars($h['hospital_name']) ?> (Admin: <?= htmlspecialchars($h['name']) ?>)
</a>
<?php endforeach; ?>
<?php else: ?>
<h3>Emergency Video Call → <?= htmlspecialchars($selected_hospital_name) ?></h3>
<div class="row">
<video id="local" autoplay playsinline muted></video>
<video id="remote" autoplay playsinline></video>
</div>
<div class="actions">
<button id="endBtn">End Call</button>
<span id="status"></span>
</div>

<script>
(async()=>{
const statusEl=document.getElementById('status');
const localV=document.getElementById('local');
const remoteV=document.getElementById('remote');
let pc,callId,lastSignalId=0,pollTimer,stream;

function setStatus(s){statusEl.textContent=s;}

try{
const startRes=await fetch('start_call.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:new URLSearchParams({receiver_id:'<?= $selected_hospital_id ?>',hospital_name:'<?= htmlspecialchars($selected_hospital_name,ENT_QUOTES) ?>'})
}).then(r=>r.json());
if(!startRes.success){setStatus('❌ Failed to start call.');return;}
callId=startRes.call_id;
setStatus('⏳ Waiting for hospital admin to join…');

stream=await navigator.mediaDevices.getUserMedia({video:true,audio:true});
localV.srcObject=stream;

pc=new RTCPeerConnection({iceServers:[{urls:'stun:stun.l.google.com:19302'}]});
stream.getTracks().forEach(t=>pc.addTrack(t,stream));
pc.ontrack=e=>{remoteV.srcObject=e.streams[0];};

pc.onicecandidate=async(e)=>{
if(e.candidate){
await fetch('send_signal.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({call_id:callId,type:'candidate',payload:JSON.stringify(e.candidate)})});
}
};

const offer=await pc.createOffer();
await pc.setLocalDescription(offer);
await fetch('send_signal.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({call_id:callId,type:'offer',payload:JSON.stringify(offer)})});

async function poll(){
const res=await fetch(`get_signals.php?call_id=${callId}&last_id=${lastSignalId}`).then(r=>r.json());
if(!res.ok)return;
for(const s of res.signals){
lastSignalId=s.id;
switch(s.type){
case 'answer':
await pc.setRemoteDescription(new RTCSessionDescription(JSON.parse(s.payload)));
setStatus('✅ Connected to hospital');break;
case 'candidate':
try{await pc.addIceCandidate(new RTCIceCandidate(JSON.parse(s.payload)))}catch(e){console.warn(e);};break;
case 'end':
setStatus('❌ Call ended by hospital');cleanup();break;
}}}
pollTimer=setInterval(poll,1000);

}catch(err){console.error(err);setStatus('❌ Error: '+err.message);}

async function cleanup(){
clearInterval(pollTimer);
try{pc.close();}catch{}
if(stream)stream.getTracks().forEach(t=>t.stop());
}

document.getElementById('endBtn').onclick=async()=>{
await fetch('end_call.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({call_id:callId})});
await fetch('send_signal.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({call_id:callId,type:'end',payload:'{}'})});
setStatus('✅ You ended the call');cleanup();
};
})();
</script>

<?php endif; ?>
</body>
</html>
