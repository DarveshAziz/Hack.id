<?php
require 'config.php';
require 'lib/messages.php';
if (!isset($_SESSION['user_id'])) header('Location: login.php');
// DEBUG: show current user_id
//echo '<pre style="background:#fdd;padding:.5rem;">'
//   . 'SESSION user_id = '
//   . htmlspecialchars($_SESSION['user_id'] ?? 'NOT SET')
//   . "</pre>";
$cid = (int)($_GET['cid'] ?? 0);
if (!$cid) die('Conversation not found');

// ── NEW: look up the two participants and pick the other one
$conv = $mysqli
  ->query("SELECT user1_id, user2_id FROM conversations WHERE id = $cid")
  ->fetch_assoc();
$otherId = ($conv['user1_id'] == $_SESSION['user_id'])
             ? $conv['user2_id']
             : $conv['user1_id'];
//echo '<pre style="background:#fdd;padding:.5rem;">'
//   . 'OTHER user_id = '
//   . htmlspecialchars($otherId ?? 'NOT SET')
//   . "</pre>";
$messages = fetchMessages($mysqli, $cid);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><title>Chat</title>
<!-- Font -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@0,400;0,600&display=swap" rel="stylesheet" />
<link href="css/style.css" rel="stylesheet">
<style>
:root {
  --primary: #8938ed;
  --secondary: #6132d7;
  --card-bg: #2a2a2a;
  --text-color: #f0f0f0;
  --bubble-me: var(--primary);
  --bubble-you: #333;
  --bubble-me-text: #fff;
  --bubble-you-text: #f0f0f0;
}
body {
  background: #202020;
  font-family: 'Poppins', sans-serif;
  color: var(--text-color);
}
.chat-outer {
  max-width: 480px;
  margin: 40px auto;
  background: var(--card-bg);
  border-radius: 18px;
  box-shadow: 0 4px 32px #0002;
  padding: 0;
  display: flex;
  flex-direction: column;
  min-height: 80vh;
  height: 80vh;
}
.chat-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.2rem 1.5rem 1rem 1.5rem;
  border-bottom: 1px solid #333;
  background: #232323;
  border-top-left-radius: 18px;
  border-top-right-radius: 18px;
}
.chat-header .avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--secondary);
  color: #fff;
  font-weight: 600;
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.chat-header .user-info {
  flex: 1;
}
.chat-header .user-info .name {
  font-weight: 600;
  color: var(--primary);
  font-size: 1.1rem;
}
.chat-header .user-info .desc {
  font-size: .85rem;
  color: #aaa;
}
.chat-box {
  flex: 1 1 0;
  overflow-y: auto;
  padding: 1.2rem 1rem 1.2rem 1rem;
  background: #232323;
  display: flex;
  flex-direction: column;
  gap: .5rem;
}
.msg {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin: 0.25rem 0;
}
.msg.me {
  align-items: flex-end;
}
.msg-bubble {
  max-width: 80%;
  padding: .7rem 1.1rem;
  border-radius: 1.2rem;
  margin-bottom: 2px;
  font-size: 1rem;
  background: var(--bubble-you);
  color: var(--bubble-you-text);
  box-shadow: 0 2px 8px #0002;
  word-break: break-word;
  position: relative;
  transition: background .2s;
}
.msg.me .msg-bubble {
  background: var(--bubble-me);
  color: var(--bubble-me-text);
  border-bottom-right-radius: .5rem;
}
.msg.you .msg-bubble {
  background: var(--bubble-you);
  color: var(--bubble-you-text);
  border-bottom-left-radius: .5rem;
}
.msg small {
  font-size: .75rem;
  color: #aaa;
  margin-bottom: 2px;
  margin-top: 2px;
  padding-left: 2px;
  padding-right: 2px;
}
/* Chat input */
.chat-input-area {
  border-top: 1px solid #333;
  background: #232323;
  padding: .8rem 1rem;
  border-bottom-left-radius: 18px;
  border-bottom-right-radius: 18px;
}
#sendForm {
  display: flex;
  gap: .5rem;
  align-items: center;
  margin: 0;
}
#sendForm input[name="body"] {
  flex: 1;
  border-radius: 2rem;
  border: none;
  background: #333;
  color: #fff;
  padding: .7rem 1.2rem;
  font-size: 1rem;
  outline: none;
  transition: border .2s;
}
#sendForm input[name="body"]:focus {
  border: 2px solid var(--primary);
  background: #292929;
}
#sendForm button {
  border-radius: 2rem;
  background: var(--primary);
  color: #fff;
  border: none;
  padding: .7rem 1.5rem;
  font-weight: 600;
  font-size: 1rem;
  transition: background .2s;
}
#sendForm button:hover {
  background: var(--secondary);
}
/* Responsive */
@media (max-width: 600px) {
  .chat-outer {
    max-width: 100vw;
    min-height: 100vh;
    border-radius: 0;
    margin: 0;
    height: 100vh;
  }
  .chat-header, .chat-input-area {
    padding-left: .7rem;
    padding-right: .7rem;
  }
  .chat-box {
    padding-left: .7rem;
    padding-right: .7rem;
  }
}
</style>
</head>
<body>
<div class="chat-outer">
  <div class="chat-header">
    <a href="profile_public.php?id=<?= $otherId ?>" class="btn btn-outline-secondary btn-sm" style="border-radius:50%;padding:6px 12px;font-size:1.1rem;background:#232323;border:none;color:#fff;">
      &larr;
    </a>
    <div class="avatar"><?= strtoupper(substr($otherId,0,1)) ?></div>
    <div class="user-info">
      <div class="name">User #<?= htmlspecialchars($otherId) ?></div>
      <div class="desc">Private Chat</div>
    </div>
  </div>
  <div class="chat-box" id="chat">
    <?php foreach($messages as $m): ?>
      <div class="msg <?= $m['sender_id']==$_SESSION['user_id']?'me':'you' ?>">
        <div class="msg-bubble">
          <small><?= htmlspecialchars($m['sent_at']) ?></small>
          <?= nl2br(htmlspecialchars($m['body'])) ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="chat-input-area">
    <form id="sendForm" autocomplete="off">
      <input name="body" autocomplete="off" placeholder="Type a message..." />
      <button type="submit">Send</button>
    </form>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
const myId   = <?= (int)$_SESSION['user_id'] ?>;
let   lastId = <?= end($messages)['id'] ?? 0 ?>;
const cid    = <?= $cid ?>;

function renderMsg(m) {
  $('#chat').append(`
    <div class="msg ${m.sender_id==myId?'me':'you'}">
      <div class="msg-bubble">
        <small>${m.sent_at}</small>
        ${$('<div>').text(m.body).html().replace(/\n/g,'<br>')}
      </div>
    </div>
  `);
}

function scrollBottom(){
  const chat = document.getElementById('chat');
  chat.scrollTop = chat.scrollHeight;
}
scrollBottom();

$('#sendForm').on('submit', e => {
  e.preventDefault();
  const txt = $('input[name=body]').val().trim();
  if (!txt) return;

  $.post(
    'api/message_send.php',
    { cid, body: txt },
    function(res) {
      if (!res.ok) {
        return alert('Send failed: ' + (res.error||'unknown'));
      }
      const safe = $('<div>').text(txt).html().replace(/\n/g,'<br>');
      $('#chat').append(`
        <div class="msg me">
          <div class="msg-bubble">
            <small>just now</small>${safe}
          </div>
        </div>
      `);
      lastId = res.id;
      scrollBottom();
      $('input[name=body]').val('');
    },
    'json'
  ).fail(xhr => {
    alert('Send error: ' + xhr.responseText);
  });
});

setInterval(()=>{
  $.getJSON('api/messages_poll.php', { cid, after: lastId })
    .done(data => {
      if (!data.length) return;
      data.forEach(m => renderMsg(m));
      lastId = data[data.length-1].id;
      scrollBottom();
    })
    .fail((xhr, status, err) => {
      console.error('Polling error', err);
    });
}, 2500);
</script>
</body>
</html>
