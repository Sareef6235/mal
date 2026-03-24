<?php
// index.php: Main chat UI with embedded CSS/JS. Production-oriented single-page app.
declare(strict_types=1);
$ticketId = isset($_GET['ticket_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['ticket_id']) : 'global';
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;
$userName = isset($_GET['name']) ? trim($_GET['name']) : 'Guest';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Realtime Chat Notifications</title>
  <style>
    :root {
      --bg: #08101f;
      --card: rgba(18, 29, 55, 0.62);
      --glass: rgba(255, 255, 255, 0.08);
      --text: #e8f2ff;
      --muted: #93accf;
      --cyan: #00e5ff;
      --blue: #3f7bff;
      --danger: #ff5d6c;
      --ok: #57ffb1;
      --radius: 18px;
      --shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0; font-family: Inter, Segoe UI, system-ui, sans-serif; color: var(--text);
      background: radial-gradient(circle at 10% 10%, #143663 0%, #08101f 50%, #050911 100%);
      min-height: 100vh; overflow-x: hidden;
      cursor: crosshair;
    }
    .cursor-glow {
      position: fixed; width: 130px; height: 130px; pointer-events: none; border-radius: 50%;
      background: radial-gradient(circle, rgba(0,229,255,.2), rgba(63,123,255,0)); filter: blur(2px);
      transform: translate(-50%, -50%); z-index: 1;
    }
    .app {
      position: relative; z-index: 2; max-width: 1000px; margin: 24px auto; padding: 16px;
    }
    .chat-shell {
      display: grid; grid-template-rows: auto 1fr auto;
      height: min(90vh, 860px);
      background: var(--card); border: 1px solid var(--glass); border-radius: var(--radius);
      box-shadow: var(--shadow); backdrop-filter: blur(18px);
      overflow: hidden;
    }
    .header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 20px; border-bottom: 1px solid var(--glass);
    }
    .title-wrap { display: flex; flex-direction: column; }
    .title { font-weight: 700; letter-spacing: .2px; }
    .status { color: var(--muted); font-size: .9rem; }
    .actions { display: flex; gap: 10px; }
    .btn {
      border: 1px solid rgba(0,229,255,.35); color: #baf7ff; background: rgba(0, 229, 255, .08);
      padding: 8px 12px; border-radius: 12px; cursor: pointer; transition: all .2s ease;
    }
    .btn:hover { transform: translateY(-1px); box-shadow: 0 0 18px rgba(0,229,255,.25); }
    .messages {
      padding: 16px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px;
      background-image: linear-gradient(transparent 95%, rgba(255,255,255,.03)); background-size: 100% 24px;
    }
    .bubble {
      max-width: 75%; padding: 10px 12px; border-radius: 14px; position: relative;
      animation: pop .18s ease;
    }
    .incoming { align-self: flex-start; background: rgba(255,255,255,.08); }
    .outgoing { align-self: flex-end; background: rgba(63,123,255,.32); border: 1px solid rgba(63,123,255,.45); }
    .meta { display: flex; justify-content: flex-end; gap: 8px; color: #b6cef0; font-size: .75rem; margin-top: 6px; }
    .ticks.seen { color: var(--ok); }
    .typing { color: var(--cyan); font-size: .9rem; min-height: 18px; padding: 0 16px 10px; }
    .composer {
      display: grid; grid-template-columns: 1fr auto; gap: 10px;
      padding: 14px; border-top: 1px solid var(--glass);
      background: rgba(7, 13, 26, .65);
    }
    .composer input {
      width: 100%; border: 1px solid rgba(255,255,255,.15); background: rgba(255,255,255,.08);
      color: var(--text); border-radius: 12px; padding: 12px 14px; outline: none;
    }
    .composer input:focus { border-color: rgba(0,229,255,.55); box-shadow: 0 0 0 2px rgba(0,229,255,.15); }
    .toast {
      position: fixed; right: 20px; bottom: 18px; background: rgba(4,12,24,.92); border: 1px solid rgba(0,229,255,.3);
      color: #d9f5ff; padding: 12px 16px; border-radius: 12px; transform: translateY(20px); opacity: 0;
      transition: all .28s ease; z-index: 30;
    }
    .toast.show { transform: translateY(0); opacity: 1; }

    .settings-panel {
      position: fixed; top: 0; right: -380px; width: min(92vw, 380px); height: 100%;
      background: rgba(5, 11, 24, .9); border-left: 1px solid rgba(0,229,255,.25);
      backdrop-filter: blur(18px); box-shadow: -18px 0 50px rgba(0,0,0,.35);
      transition: right .35s ease; z-index: 25; padding: 18px; overflow-y: auto;
    }
    .settings-panel.open { right: 0; }
    .overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,.35); opacity: 0; pointer-events: none;
      transition: opacity .25s ease; z-index: 24;
    }
    .overlay.open { opacity: 1; pointer-events: auto; }
    .panel-title { font-weight: 700; margin-bottom: 10px; }
    .field { margin: 12px 0; }
    label { font-size: .85rem; color: #b4cff7; display: block; margin-bottom: 8px; }
    input[type="range"] { width: 100%; accent-color: var(--cyan); }
    .sound-list { display: grid; gap: 8px; }
    .sound-item {
      border: 1px solid rgba(255,255,255,.13); background: rgba(255,255,255,.04);
      border-radius: 12px; padding: 10px; display: flex; justify-content: space-between; align-items: center;
      transition: all .25s ease;
    }
    .sound-item:hover { border-color: rgba(0,229,255,.4); transform: translateX(2px); }
    .sound-item.active { border-color: var(--cyan); box-shadow: 0 0 16px rgba(0,229,255,.22); }
    .sound-item button { margin-left: 6px; }
    .switch { display: flex; justify-content: space-between; align-items: center; }
    .small { font-size: .82rem; color: var(--muted); }
    @keyframes pop { from { opacity: 0; transform: scale(.95) translateY(4px);} to { opacity: 1; transform: scale(1) translateY(0);} }
    @media (max-width: 700px) {
      .app { margin: 0; padding: 0; }
      .chat-shell { border-radius: 0; height: 100vh; }
      .bubble { max-width: 87%; }
    }
  </style>
</head>
<body>
  <div class="cursor-glow" id="cursorGlow"></div>
  <div class="app">
    <div class="chat-shell">
      <header class="header">
        <div class="title-wrap">
          <div class="title">Support Chat · Ticket <?= htmlspecialchars($ticketId, ENT_QUOTES, 'UTF-8') ?></div>
          <div class="status" id="onlineStatus">Connecting…</div>
        </div>
        <div class="actions">
          <button class="btn" id="settingsBtn">Settings</button>
        </div>
      </header>

      <main class="messages" id="messages"></main>
      <div class="typing" id="typingIndicator"></div>

      <form class="composer" id="chatForm">
        <input id="messageInput" autocomplete="off" maxlength="2000" placeholder="Type a message..." />
        <button class="btn" type="submit">Send</button>
      </form>
    </div>
  </div>

  <aside class="settings-panel" id="settingsPanel">
    <div class="panel-title">Settings</div>
    <div class="field switch">
      <label>Auto Sync</label>
      <button class="btn" id="autoSyncToggle">Toggle</button>
    </div>
    <div class="small" id="autoSyncText">Auto sync active</div>

    <div class="field">
      <label>Mute / Unmute</label>
      <button class="btn" id="muteToggle">Mute</button>
    </div>

    <div class="field">
      <label>Volume</label>
      <input type="range" id="volume" min="0" max="1" step="0.01" value="0.8" />
    </div>

    <div class="field">
      <label>Notification Sound</label>
      <div class="sound-list" id="soundList"></div>
    </div>

    <div class="field">
      <label>Upload Custom Sound (admin)</label>
      <input id="customSound" type="file" accept="audio/mpeg" />
      <button class="btn" id="uploadBtn">Upload</button>
      <div class="small" id="uploadMsg"></div>
    </div>
  </aside>
  <div class="overlay" id="overlay"></div>

  <div class="toast" id="toast"></div>

  <script>
    window.APP_CONFIG = {
      ticketId: <?= json_encode($ticketId) ?>,
      userId: <?= (int)$userId ?>,
      userName: <?= json_encode($userName) ?>,
      endpoints: {
        send: 'send.php',
        fetch: 'fetch.php',
        count: 'count.php',
        upload: 'upload.php'
      },
      firebase: {
        apiKey: 'YOUR_API_KEY',
        authDomain: 'YOUR_PROJECT.firebaseapp.com',
        databaseURL: 'https://YOUR_PROJECT-default-rtdb.firebaseio.com',
        projectId: 'YOUR_PROJECT',
        appId: 'YOUR_APP_ID'
      }
    };
  </script>
  <script type="module" src="settings.js"></script>
  <script type="module" src="firebase.js"></script>
</body>
</html>
