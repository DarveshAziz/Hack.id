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
<!doctype html><html><head>
<meta charset="utf-8"><title>Chat</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<style>
.chat-box{height:65vh;overflow-y:auto;border:1px solid #ddd;padding:1rem;}
.msg{margin:.25rem 0}
.msg.me  {text-align:right}
.msg.you {text-align:left}
</style>
</head><body class="p-3">
<a href="profile_public.php?id=<?= $otherId ?>"
     class="btn btn-outline-secondary mb-3">
    &larr; Back to profile
  </a>
<div class="chat-box" id="chat">
<?php foreach($messages as $m): ?>
  <div class="msg <?= $m['sender_id']==$_SESSION['user_id']?'me':'you' ?>">
    <small><?= htmlspecialchars($m['sent_at']) ?></small><br>
    <?= nl2br(htmlspecialchars($m['body'])) ?>
  </div>
<?php endforeach; ?>
</div>

<form id="sendForm" class="d-flex mt-2">
  <input name="body" autocomplete="off" class="form-control me-2">
  <button class="btn btn-primary">Send</button>
</form>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
const myId   = <?= (int)$_SESSION['user_id'] ?>;
let   lastId = <?= end($messages)['id'] ?? 0 ?>;
const cid    = <?= $cid ?>;

function renderMsg(m) {
  $('#chat').append(`
    <div class="msg ${m.sender_id==myId?'me':'you'}">
      <small>${m.sent_at}</small><br>
      ${m.body.replace(/\n/g,'<br>')}
    </div>
  `);
}

function scrollBottom(){
  $('#chat').scrollTop($('#chat')[0].scrollHeight);
}
scrollBottom();

/* ───── send ───── */
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
      // append sender’s bubble immediately
      const safe = $('<div>').text(txt).html().replace(/\n/g,'<br>');
      $('#chat').append(`
        <div class="msg me">
          <small>just now</small><br>${safe}
        </div>
      `);
      lastId = res.id;      // bump
      scrollBottom();
      $('input[name=body]').val('');
    },
    'json' /* <— tell jQuery to parse JSON */
  ).fail(xhr => {
    alert('Send error: ' + xhr.responseText);
  });
});

/* ───── poll ───── */
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

</body></html>
