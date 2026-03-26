<?php
/**
 * Web SIP Phone - SIP.js powered softphone
 * 
 * Configuration: Edit the $config array below with your SIP server details.
 * Requirements: A WebSocket-enabled SIP server (Asterisk, FreeSWITCH, Kamailio, etc.)
 */

$config = [
    'wsServer'   => 'wss://your-sip-server.com:8089/ws', // WebSocket URI of your SIP server
    'sipDomain'  => 'your-sip-domain.com',               // SIP domain / realm
    'username'   => 'your-sip-username',                 // SIP username
    'password'   => 'your-sip-password',                 // SIP password
    'displayName'=> 'Web Phone',                         // Caller display name
    'stunServer' => 'stun:stun.l.google.com:19302',      // STUN server for NAT traversal
];

// Optionally override config from GET params (for dev/testing only — remove in production)
// foreach (['wsServer','sipDomain','username','password','displayName'] as $k) {
//     if (isset($_GET[$k])) $config[$k] = htmlspecialchars($_GET[$k]);
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Web SIP Phone</title>

<!-- SIP.js from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/sip.js/0.21.2/sip.min.js"></script>

<style>
  /* ── Fonts ── */
  @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Barlow:wght@300;400;600;700&display=swap');

  :root {
    --bg:        #0d0f12;
    --panel:     #13171d;
    --border:    #1f2530;
    --accent:    #00e5a0;
    --accent2:   #00aaff;
    --danger:    #ff3b5c;
    --warn:      #ffb800;
    --text:      #d4dce8;
    --muted:     #4a5568;
    --mono:      'Share Tech Mono', monospace;
    --sans:      'Barlow', sans-serif;
    --radius:    6px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--sans);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background-image:
      radial-gradient(ellipse 80% 50% at 50% -10%, rgba(0,229,160,.07) 0%, transparent 60%),
      repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(255,255,255,.02) 40px),
      repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(255,255,255,.02) 40px);
  }

  /* ── Header ── */
  header {
    width: 100%;
    max-width: 420px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
  }
  header .logo {
    font-family: var(--mono);
    font-size: 1.2rem;
    color: var(--accent);
    letter-spacing: .12em;
  }
  header .logo span { color: var(--muted); }
  .status-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--muted);
    margin-left: auto;
    transition: background .3s, box-shadow .3s;
  }
  .status-dot.registered  { background: var(--accent);  box-shadow: 0 0 8px var(--accent); }
  .status-dot.calling     { background: var(--warn);    box-shadow: 0 0 8px var(--warn);   animation: pulse 1s infinite; }
  .status-dot.in-call     { background: var(--accent2); box-shadow: 0 0 8px var(--accent2);}
  .status-dot.error       { background: var(--danger);  box-shadow: 0 0 8px var(--danger); }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

  /* ── Card ── */
  .card {
    width: 100%;
    max-width: 420px;
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,.5);
  }

  /* ── Status bar ── */
  .status-bar {
    font-family: var(--mono);
    font-size: .72rem;
    letter-spacing: .08em;
    padding: 8px 16px;
    background: rgba(0,0,0,.3);
    border-bottom: 1px solid var(--border);
    color: var(--muted);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .status-bar #statusText { color: var(--text); }

  /* ── Display ── */
  .display {
    padding: 20px 20px 12px;
  }
  #callTimer {
    font-family: var(--mono);
    font-size: .78rem;
    color: var(--accent2);
    letter-spacing: .1em;
    min-height: 18px;
    margin-bottom: 4px;
  }
  #dialDisplay {
    font-family: var(--mono);
    font-size: 2rem;
    letter-spacing: .08em;
    color: #fff;
    min-height: 48px;
    word-break: break-all;
    line-height: 1.2;
  }
  #callerInfo {
    font-size: .8rem;
    color: var(--muted);
    margin-top: 4px;
    min-height: 20px;
    font-style: italic;
  }

  /* ── Divider ── */
  .divider { height: 1px; background: var(--border); margin: 0 20px; }

  /* ── Keypad ── */
  .keypad {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 16px 20px;
  }
  .key {
    background: rgba(255,255,255,.04);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: var(--mono);
    font-size: 1.1rem;
    padding: 14px 0;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    transition: background .12s, border-color .12s, transform .08s;
    user-select: none;
    line-height: 1;
  }
  .key sub { font-size: .5rem; color: var(--muted); letter-spacing: .1em; }
  .key:hover  { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.15); }
  .key:active { transform: scale(.93); background: rgba(255,255,255,.12); }

  /* ── Action row ── */
  .actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 0 20px 20px;
  }
  .btn {
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-family: var(--sans);
    font-weight: 600;
    font-size: .8rem;
    letter-spacing: .06em;
    padding: 12px 8px;
    text-transform: uppercase;
    transition: opacity .15s, transform .1s;
    display: flex; flex-direction: column;
    align-items: center; gap: 5px;
  }
  .btn svg { width: 20px; height: 20px; }
  .btn:active { transform: scale(.94); }
  .btn:disabled { opacity: .3; cursor: not-allowed; }

  .btn-call    { background: var(--accent);  color: #001a0f; }
  .btn-hangup  { background: var(--danger);  color: #fff; }
  .btn-mute    { background: rgba(255,255,255,.07); color: var(--text); border: 1px solid var(--border); }
  .btn-hold    { background: rgba(255,255,255,.07); color: var(--text); border: 1px solid var(--border); }
  .btn-clear   { background: rgba(255,255,255,.07); color: var(--text); border: 1px solid var(--border); }
  .btn-transfer{ background: rgba(255,255,255,.07); color: var(--text); border: 1px solid var(--border); }

  .btn-mute.active  { background: var(--warn);   color: #1a1000; border-color: var(--warn); }
  .btn-hold.active  { background: var(--accent2); color: #001020; border-color: var(--accent2); }

  /* Incoming call overlay */
  .incoming-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.75);
    backdrop-filter: blur(6px);
    z-index: 100;
    align-items: center;
    justify-content: center;
  }
  .incoming-overlay.show { display: flex; }
  .incoming-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 32px 40px;
    text-align: center;
    box-shadow: 0 0 0 1px var(--accent), 0 32px 80px rgba(0,229,160,.15);
    animation: ring .6s ease-in-out infinite alternate;
  }
  @keyframes ring { from{box-shadow: 0 0 0 1px var(--accent), 0 32px 80px rgba(0,229,160,.15)} to{box-shadow: 0 0 0 4px var(--accent), 0 32px 80px rgba(0,229,160,.3)} }
  .incoming-card h2 { font-size: 1rem; color: var(--muted); letter-spacing: .1em; text-transform: uppercase; font-weight: 400; margin-bottom: 8px; }
  .incoming-card .caller { font-family: var(--mono); font-size: 1.4rem; color: #fff; margin-bottom: 24px; }
  .incoming-actions { display: flex; gap: 16px; justify-content: center; }
  .btn-answer { background: var(--accent); color: #001a0f; border: none; border-radius: 50px; padding: 14px 28px; font-weight: 700; font-size: .9rem; cursor: pointer; letter-spacing: .08em; }
  .btn-reject { background: var(--danger);  color: #fff;     border: none; border-radius: 50px; padding: 14px 28px; font-weight: 700; font-size: .9rem; cursor: pointer; letter-spacing: .08em; }

  /* Log */
  .log-section { max-width: 420px; width: 100%; margin-top: 16px; }
  .log-section summary { font-family: var(--mono); font-size: .72rem; color: var(--muted); letter-spacing: .08em; cursor: pointer; user-select: none; }
  #eventLog {
    background: rgba(0,0,0,.4);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 10px 12px;
    font-family: var(--mono);
    font-size: .68rem;
    color: var(--muted);
    max-height: 160px;
    overflow-y: auto;
    margin-top: 8px;
    line-height: 1.7;
  }
  #eventLog .log-ok   { color: var(--accent); }
  #eventLog .log-err  { color: var(--danger); }
  #eventLog .log-info { color: var(--accent2); }
  #eventLog .log-warn { color: var(--warn); }

  /* Audio elements hidden */
  audio { display: none; }
</style>
</head>
<body>

<!-- Hidden audio elements -->
<audio id="remoteAudio" autoplay></audio>
<audio id="ringtone" loop>
  <source src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAA=" />
</audio>

<!-- Incoming call overlay -->
<div class="incoming-overlay" id="incomingOverlay">
  <div class="incoming-card">
    <h2>Incoming Call</h2>
    <div class="caller" id="incomingCaller">Unknown</div>
    <div class="incoming-actions">
      <button class="btn-answer" onclick="answerCall()">&#9742; Answer</button>
      <button class="btn-reject" onclick="rejectCall()">&#9587; Reject</button>
    </div>
  </div>
</div>

<!-- Header -->
<header>
  <div class="logo">SIP<span>.</span>PHONE</div>
  <div class="status-dot" id="statusDot"></div>
</header>

<!-- Main card -->
<div class="card">

  <!-- Status bar -->
  <div class="status-bar">
    <span id="statusText">Connecting…</span>
    <span id="sipUser" style="color:var(--accent);font-size:.68rem"><?= htmlspecialchars($config['username']) ?>@<?= htmlspecialchars($config['sipDomain']) ?></span>
  </div>

  <!-- Display -->
  <div class="display">
    <div id="callTimer"></div>
    <div id="dialDisplay">_</div>
    <div id="callerInfo"></div>
  </div>

  <div class="divider"></div>

  <!-- Keypad -->
  <div class="keypad">
    <?php
    $keys = [
      ['1',''],['2','ABC'],['3','DEF'],
      ['4','GHI'],['5','JKL'],['6','MNO'],
      ['7','PQRS'],['8','TUV'],['9','WXYZ'],
      ['*',''],['0','+'],['#',''],
    ];
    foreach ($keys as $key): $digit = $key[0]; $letters = $key[1];
    ?>
    <button class="key" onclick="pressKey('<?= $digit ?>')">
      <?= $digit ?>
      <?php if ($letters): ?><sub><?= $letters ?></sub><?php endif; ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Actions -->
  <div class="actions">

    <!-- Call -->
    <button class="btn btn-call" id="btnCall" onclick="makeCall()">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
      Call
    </button>

    <!-- Hangup -->
    <button class="btn btn-hangup" id="btnHangup" onclick="hangup()" disabled>
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.4 13.6l-2.6-.3c-.6-.1-1.2.1-1.6.5l-1.9 1.9c-2.8-1.5-5.1-3.7-6.6-6.6l1.9-1.9c.4-.4.6-1 .5-1.6l-.3-2.6C9.5 2.1 8.6 1.3 7.6 1.3H4.3c-1.1 0-2.1.9-2 2 .4 7.5 6.4 13.5 13.8 13.8 1.1 0 2-.9 2-2v-3.3c.1-1-.7-1.9-1.7-2.2z"/></svg>
      Hang Up
    </button>

    <!-- Clear -->
    <button class="btn btn-clear" onclick="clearDisplay()">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.4L17.6 5 12 10.6 6.4 5 5 6.4l5.6 5.6L5 17.6 6.4 19l5.6-5.6 5.6 5.6 1.4-1.4-5.6-5.6z"/></svg>
      Clear
    </button>

    <!-- Mute -->
    <button class="btn btn-mute" id="btnMute" onclick="toggleMute()" disabled>
      <svg viewBox="0 0 24 24" fill="currentColor" id="muteIcon"><path d="M12 14c1.7 0 3-1.3 3-3V5c0-1.7-1.3-3-3-3S9 3.3 9 5v6c0 1.7 1.3 3 3 3zm-1 1.9V18H9v2h6v-2h-2v-2.1c2.8-.5 5-2.9 5-5.9h-2c0 2.2-1.8 4-4 4s-4-1.8-4-4H6c0 3 2.2 5.4 5 5.9z"/></svg>
      Mute
    </button>

    <!-- Hold -->
    <button class="btn btn-hold" id="btnHold" onclick="toggleHold()" disabled>
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
      Hold
    </button>

    <!-- Transfer (DTMF during call) -->
    <button class="btn btn-transfer" id="btnDTMF" onclick="sendDTMF()" disabled title="Send DTMF">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h2v2H4zm0 4h2v2H4zm0 4h2v2H4zm4-8h2v2H8zm0 4h2v2H8zm0 4h2v2H8zm4-8h2v2h-2zm0 4h2v2h-2zm0 4h2v2h-2zm4-8h2v2h-2zm0 4h2v2h-2zm0 4h2v2h-2z"/></svg>
      DTMF
    </button>

  </div>
</div>

<!-- Event log -->
<details class="log-section">
  <summary>▸ Event Log</summary>
  <div id="eventLog"></div>
</details>

<script>
/* ══════════════════════════════════════════════
   PHP-injected config
══════════════════════════════════════════════ */
const CFG = {
  wsServer:    "<?= addslashes($config['wsServer']) ?>",
  sipDomain:   "<?= addslashes($config['sipDomain']) ?>",
  username:    "<?= addslashes($config['username']) ?>",
  password:    "<?= addslashes($config['password']) ?>",
  displayName: "<?= addslashes($config['displayName']) ?>",
  stunServer:  "<?= addslashes($config['stunServer']) ?>",
};

/* ══════════════════════════════════════════════
   State
══════════════════════════════════════════════ */
let userAgent     = null;
let currentSession= null;
let isMuted       = false;
let isOnHold      = false;
let timerInterval = null;
let dialBuffer    = '';
let pendingInvite  = null;  // incoming session before answer

/* ══════════════════════════════════════════════
   DOM references
══════════════════════════════════════════════ */
const $dot       = document.getElementById('statusDot');
const $status    = document.getElementById('statusText');
const $display   = document.getElementById('dialDisplay');
const $caller    = document.getElementById('callerInfo');
const $timer     = document.getElementById('callTimer');
const $btnCall   = document.getElementById('btnCall');
const $btnHangup = document.getElementById('btnHangup');
const $btnMute   = document.getElementById('btnMute');
const $btnHold   = document.getElementById('btnHold');
const $btnDTMF   = document.getElementById('btnDTMF');
const $overlay   = document.getElementById('incomingOverlay');
const $incomingCaller = document.getElementById('incomingCaller');
const $log       = document.getElementById('eventLog');
const $remoteAudio = document.getElementById('remoteAudio');

/* ══════════════════════════════════════════════
   Logging
══════════════════════════════════════════════ */
function log(msg, type = 'info') {
  const t = new Date().toLocaleTimeString();
  const line = document.createElement('div');
  line.className = `log-${type}`;
  line.textContent = `[${t}] ${msg}`;
  $log.prepend(line);
}

/* ══════════════════════════════════════════════
   UI helpers
══════════════════════════════════════════════ */
function setStatus(text, dotClass = '') {
  $status.textContent = text;
  $dot.className = 'status-dot ' + dotClass;
}

function setCallButtons(inCall) {
  $btnCall.disabled   = inCall;
  $btnHangup.disabled = !inCall;
  $btnMute.disabled   = !inCall;
  $btnHold.disabled   = !inCall;
  $btnDTMF.disabled   = !inCall;
}

function startTimer() {
  let secs = 0;
  timerInterval = setInterval(() => {
    secs++;
    const m = String(Math.floor(secs / 60)).padStart(2, '0');
    const s = String(secs % 60).padStart(2, '0');
    $timer.textContent = `● ${m}:${s}`;
  }, 1000);
}

function stopTimer() {
  clearInterval(timerInterval);
  timerInterval = null;
  $timer.textContent = '';
}

function resetUI() {
  setCallButtons(false);
  setStatus('Registered', 'registered');
  stopTimer();
  dialBuffer = '';
  $display.textContent = '_';
  $caller.textContent = '';
  isMuted = false;
  isOnHold = false;
  $btnMute.classList.remove('active');
  $btnHold.classList.remove('active');
  $overlay.classList.remove('show');
}

/* ══════════════════════════════════════════════
   Keypad input
══════════════════════════════════════════════ */
function pressKey(digit) {
  // If in a call, send DTMF
  if (currentSession && currentSession.state === SIP.SessionState.Established) {
    currentSession.sessionDescriptionHandler.sendDtmf(digit);
    log(`DTMF sent: ${digit}`, 'info');
    return;
  }
  dialBuffer += digit;
  $display.textContent = dialBuffer || '_';
}

function clearDisplay() {
  dialBuffer = '';
  $display.textContent = '_';
}

document.addEventListener('keydown', e => {
  if (/^[0-9*#]$/.test(e.key)) pressKey(e.key);
  if (e.key === 'Backspace') {
    dialBuffer = dialBuffer.slice(0, -1);
    $display.textContent = dialBuffer || '_';
  }
  if (e.key === 'Enter' && !currentSession) makeCall();
  if (e.key === 'Escape' && currentSession) hangup();
});

/* ══════════════════════════════════════════════
   SIP.js — UserAgent setup
══════════════════════════════════════════════ */
function initSIP() {
  const uri = SIP.UserAgent.makeURI('sip:' + CFG.username + '@' + CFG.sipDomain);
  if (!uri) { log('Invalid SIP URI — check username/domain', 'err'); return; }

  const userAgentOptions = {
    uri: uri,
    transportOptions: {
      server: CFG.wsServer,
      traceSip: true,         // required: enables sending REGISTER/INVITE packets
      keepAliveInterval: 20,  // WS ping interval to prevent NAT timeout
    },
    authorizationUsername: CFG.username,  // sent in Authorization: header
    authorizationPassword: CFG.password,
    displayName: CFG.displayName,
    contactParams: { transport: 'ws' },   // Contact header transport param
    sessionDescriptionHandlerFactoryOptions: {
      peerConnectionConfiguration: {
        iceServers: [{ urls: CFG.stunServer }],
      },
    },
    logLevel: 'warn',
    delegate: {
      onInvite: function(invitation) {
        handleIncomingCall(invitation);
      },
    },
  };

  userAgent = new SIP.UserAgent(userAgentOptions);

  // SIP.js 0.21.x — transport uses stateChange, not onConnect/onDisconnect
  userAgent.transport.stateChange.addListener(function(newState) {
    log('Transport: ' + newState, newState === 'Connected' ? 'ok' : 'info');
    if (newState === 'Connected') {
      register();
    } else if (newState === 'Disconnected') {
      log('WebSocket disconnected', 'err');
      setStatus('Disconnected', 'error');
      registerer = null; // reset so re-connect triggers fresh REGISTER
    }
  });

  log('Connecting to ' + CFG.wsServer + '...');
  setStatus('Connecting...');
  userAgent.start().catch(function(err) {
    log('UA start failed: ' + err.message, 'err');
    setStatus('Error', 'error');
  });
}

var registerer = null;

function register() {
  if (registerer) return; // prevent duplicate REGISTER on reconnect

  registerer = new SIP.Registerer(userAgent, {
    expires: 300,
    registrarServer: 'sip:' + CFG.sipDomain,
  });

  registerer.stateChange.addListener(function(state) {
    log('Registerer: ' + state, 'info');
    if (state === SIP.RegistererState.Registered) {
      log('Registered as ' + CFG.username + '@' + CFG.sipDomain, 'ok');
      setStatus('Registered', 'registered');
    } else if (state === SIP.RegistererState.Unregistered) {
      log('Unregistered', 'warn');
      setStatus('Unregistered', 'error');
    } else if (state === SIP.RegistererState.Terminated) {
      registerer = null;
    }
  });

  registerer.register().catch(function(err) {
    log('Registration error: ' + err.message, 'err');
    setStatus('Reg failed', 'error');
  });
}

/* ══════════════════════════════════════════════
   Outbound call
══════════════════════════════════════════════ */
function makeCall() {
  const target = dialBuffer.trim();
  if (!target) { log('Enter a number first', 'warn'); return; }
  if (!userAgent) { log('SIP not ready', 'err'); return; }

  const targetURI = SIP.UserAgent.makeURI(`sip:${target}@${CFG.sipDomain}`);
  if (!targetURI) { log('Invalid SIP URI', 'err'); return; }

  const inviter = new SIP.Inviter(userAgent, targetURI, {
    sessionDescriptionHandlerOptions: { constraints: { audio: true, video: false } },
  });

  setupSession(inviter, target, false);

  inviter.invite().then(() => {
    log(`Calling ${target}…`, 'info');
    setStatus('Calling…', 'calling');
  }).catch(err => {
    log('Call failed: ' + err.message, 'err');
    resetUI();
  });
}

/* ══════════════════════════════════════════════
   Incoming call
══════════════════════════════════════════════ */
function handleIncomingCall(invitation) {
  pendingInvite = invitation;
  const callerDisplay = invitation.remoteIdentity.displayName
    || invitation.remoteIdentity.uri.user
    || 'Unknown';

  log(`Incoming call from ${callerDisplay}`, 'warn');
  $incomingCaller.textContent = callerDisplay;
  $overlay.classList.add('show');
  setStatus('Incoming call', 'calling');
}

function answerCall() {
  if (!pendingInvite) return;
  $overlay.classList.remove('show');
  pendingInvite.accept({
    sessionDescriptionHandlerOptions: { constraints: { audio: true, video: false } },
  }).then(() => {
    setupSession(pendingInvite, pendingInvite.remoteIdentity.uri.user, true);
    pendingInvite = null;
  }).catch(err => {
    log('Failed to answer: ' + err.message, 'err');
    pendingInvite = null;
    resetUI();
  });
}

function rejectCall() {
  if (!pendingInvite) return;
  pendingInvite.reject();
  pendingInvite = null;
  $overlay.classList.remove('show');
  log('Call rejected', 'warn');
  setStatus('Registered', 'registered');
}

/* ══════════════════════════════════════════════
   Session management
══════════════════════════════════════════════ */
function setupSession(session, label, isIncoming) {
  currentSession = session;
  setCallButtons(true);
  $caller.textContent = isIncoming ? `← from ${label}` : `→ to ${label}`;

  session.stateChange.addListener(state => {
    log(`Session: ${state}`, 'info');
    switch (state) {
      case SIP.SessionState.Establishing:
        setStatus(isIncoming ? 'Answering…' : 'Ringing…', 'calling');
        break;
      case SIP.SessionState.Established:
        setStatus('In call', 'in-call');
        startTimer();
        attachRemoteAudio(session);
        break;
      case SIP.SessionState.Terminating:
      case SIP.SessionState.Terminated:
        log('Call ended', 'ok');
        currentSession = null;
        resetUI();
        break;
    }
  });
}

function attachRemoteAudio(session) {
  const sdh = session.sessionDescriptionHandler;
  if (!sdh) return;
  const pc = sdh.peerConnection;
  if (!pc) return;

  pc.ontrack = e => {
    if (e.streams && e.streams[0]) {
      $remoteAudio.srcObject = e.streams[0];
      $remoteAudio.play().catch(() => {});
    }
  };

  // Also handle already-received tracks
  pc.getReceivers().forEach(receiver => {
    if (receiver.track) {
      const stream = new MediaStream([receiver.track]);
      $remoteAudio.srcObject = stream;
      $remoteAudio.play().catch(() => {});
    }
  });
}

/* ══════════════════════════════════════════════
   In-call controls
══════════════════════════════════════════════ */
function hangup() {
  if (!currentSession) return;
  switch (currentSession.state) {
    case SIP.SessionState.Establishing:
      currentSession.cancel();
      break;
    case SIP.SessionState.Established:
      currentSession.bye();
      break;
    default:
      break;
  }
}

function toggleMute() {
  if (!currentSession) return;
  const pc = currentSession.sessionDescriptionHandler?.peerConnection;
  if (!pc) return;
  isMuted = !isMuted;
  pc.getSenders().forEach(s => {
    if (s.track && s.track.kind === 'audio') s.track.enabled = !isMuted;
  });
  $btnMute.classList.toggle('active', isMuted);
  log(isMuted ? 'Microphone muted' : 'Microphone unmuted', 'info');
}

function toggleHold() {
  if (!currentSession || currentSession.state !== SIP.SessionState.Established) return;
  if (isOnHold) {
    currentSession.unhold().then(() => {
      isOnHold = false;
      $btnHold.classList.remove('active');
      log('Call resumed', 'ok');
      setStatus('In call', 'in-call');
    });
  } else {
    currentSession.hold().then(() => {
      isOnHold = true;
      $btnHold.classList.add('active');
      log('Call on hold', 'warn');
      setStatus('On hold', 'calling');
    });
  }
}

function sendDTMF() {
  if (!currentSession) return;
  const tone = prompt('Enter DTMF tone(s):');
  if (!tone) return;
  for (const t of tone.trim()) {
    if (/^[0-9*#ABCD]$/.test(t)) {
      currentSession.sessionDescriptionHandler?.sendDtmf(t);
      log(`DTMF: ${t}`, 'info');
    }
  }
}

/* ══════════════════════════════════════════════
   Boot
══════════════════════════════════════════════ */
window.addEventListener('DOMContentLoaded', initSIP);
</script>
</body>
</html>
