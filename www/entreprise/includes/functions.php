<?php
require_once __DIR__ . '/db.php';
function sanitize($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function formatPrice($p) { return number_format((float)$p, 0, ',', ' ') . ' FCFA'; }
function base($path = '') { global $config; $b = isset($config['base_url']) ? $config['base_url'] : '/'; return rtrim($b, '/') . '/' . ltrim($path, '/'); }
function csrfToken() { ensureSession(); if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); } return $_SESSION['csrf']; }
function csrfField() { return '<input type="hidden" name="csrf" value="' . csrfToken() . '">'; }
function flashSet($msg, $type = 'error') { ensureSession(); $_SESSION['flash'] = ['msg'=>$msg, 'type'=>$type]; }
function flashGet() { ensureSession(); if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; } return null; }
function verifyCsrf() {
  ensureSession();
  $t = isset($_POST['csrf']) ? $_POST['csrf'] : '';
  if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
    flashSet('Votre session a expiré ou la requête est invalide. Merci de recharger la page.', 'error');
    $ref = isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : base('');
    header('Location: ' . $ref);
    exit;
  }
}
function getSetting($key, $default = null) {
  try {
    $st = db()->prepare('SELECT `value` FROM settings WHERE `key` = :k');
    $st->execute([':k'=>$key]);
    $row = $st->fetch();
    if ($row && isset($row['value'])) return $row['value'];
  } catch (Throwable $e) {}
  return $default;
}
function setSetting($key, $value) {
  $st = db()->prepare('INSERT INTO settings(`key`,`value`) VALUES(:k,:v) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
  $st->execute([':k'=>$key, ':v'=>$value]);
}
function galleryPublicEnabled() { return getSetting('gallery_public', '0') === '1'; }
function renderTemplate($tpl, $vars) { foreach ($vars as $k=>$v) { $tpl = str_replace('{{'.$k.'}}', (string)$v, $tpl); } return $tpl; }
function orderSummary($orderId) {
  $pdo = db();
  $st = $pdo->prepare('SELECT cp.id_produit, cp.quantite, p.nom, p.prix FROM commande_produits cp JOIN produits p ON p.id = cp.id_produit WHERE cp.id_commande = :id');
  $st->execute([':id'=>$orderId]);
  $rows = $st->fetchAll();
  $count = 0; $total = 0; $lines = [];
  foreach ($rows as $r) { $q=(int)$r['quantite']; $count += $q; $lineTotal = $q * (float)$r['prix']; $total += $lineTotal; $lines[] = $r['nom'] . ' x' . $q . ' = ' . formatPrice($lineTotal); }
  return ['count'=>$count, 'total'=>$total, 'lines'=>$lines];
}
function sendEmailNative($to, $subject, $body) {
  $headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=UTF-8' . "\r\n";
  $from = getSetting('smtp_from');
  if ($from) { $headers .= 'From: ' . $from . "\r\n"; ini_set('sendmail_from', $from); }
  $host = getSetting('smtp_host');
  $port = getSetting('smtp_port');
  $user = getSetting('smtp_user');
  $pass = getSetting('smtp_pass');
  $secure = getSetting('smtp_secure');
  $fromName = getSetting('smtp_from_name');
  if ($user && $pass && $host) { $ok = sendEmailSmtp($to, $subject, $body, $from ?: 'no-reply@example.com', $fromName ?: 'Notifications', $host, (int)$port ?: 587, $secure ?: 'tls', $user, $pass); logNotification('email', $to, $subject . "\n" . $body, $ok, $ok? null : 'smtp failed'); return; }
  if ($host) ini_set('SMTP', $host);
  if ($port) ini_set('smtp_port', $port);
  $ok = @mail($to, $subject, $body, $headers);
  logNotification('email', $to, $subject . "\n" . $body, $ok, $ok? null : 'mail() failed');
}
function sendEmailSmtp($to, $subject, $body, $fromEmail, $fromName, $host, $port, $secure, $user, $pass) {
  $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
  $ctx = stream_context_create();
  $fp = @stream_socket_client($remote, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
  if (!$fp) return false;
  stream_set_timeout($fp, 10);
  $r = fgets($fp);
  fwrite($fp, "EHLO localhost\r\n"); fgets($fp);
  if ($secure === 'tls') { fwrite($fp, "STARTTLS\r\n"); $resp = fgets($fp); if (strpos($resp,'220')!==0) { fclose($fp); return false; } if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($fp); return false; } fwrite($fp, "EHLO localhost\r\n"); fgets($fp); }
  fwrite($fp, "AUTH LOGIN\r\n"); fgets($fp);
  fwrite($fp, base64_encode($user) . "\r\n"); fgets($fp);
  fwrite($fp, base64_encode($pass) . "\r\n"); $authResp = fgets($fp); if (strpos($authResp,'235')!==0) { fclose($fp); return false; }
  $fromHeader = $fromName ? ("=?UTF-8?B?" . base64_encode($fromName) . "?= <" . $fromEmail . ">") : $fromEmail;
  fwrite($fp, "MAIL FROM:<" . $fromEmail . ">\r\n"); $mr=fgets($fp); if (strpos($mr,'250')!==0) { fclose($fp); return false; }
  fwrite($fp, "RCPT TO:<" . $to . ">\r\n"); $rr=fgets($fp); if (strpos($rr,'250')!==0 && strpos($rr,'251')!==0) { fclose($fp); return false; }
  fwrite($fp, "DATA\r\n"); fgets($fp);
  $headers = '';
  $headers .= 'From: ' . $fromHeader . "\r\n";
  $headers .= 'To: <' . $to . '>' . "\r\n";
  $headers .= 'Subject: ' . $subject . "\r\n";
  $headers .= 'MIME-Version: 1.0' . "\r\n";
  $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
  $data = $headers . "\r\n" . $body . "\r\n.\r\n";
  fwrite($fp, $data); $dr=fgets($fp); $ok = strpos($dr,'250')===0;
  fwrite($fp, "QUIT\r\n"); fclose($fp);
  return $ok;
}
function sendSmsTwilio($to, $text) {
  $sid = getSetting('twilio_sid');
  $token = getSetting('twilio_token');
  $from = getSetting('twilio_from');
  if (!$sid || !$token || !$from) return;
  $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $sid . '/Messages.json';
  $data = http_build_query(['To'=>$to, 'From'=>$from, 'Body'=>$text]);
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $resp = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);
  logNotification('sms', $to, $text, $code>=200 && $code<300, $code>=200 && $code<300? null : ('HTTP ' . $code . ' ' . substr((string)$resp,0,200)));
}
function sendWhatsAppGraph($to, $text) {
  $token = getSetting('whatsapp_token');
  $phoneId = getSetting('whatsapp_phone_id');
  if (!$token || !$phoneId) return;
  $url = 'https://graph.facebook.com/v19.0/' . $phoneId . '/messages';
  $payload = json_encode(['messaging_product'=>'whatsapp','to'=>$to,'type'=>'text','text'=>['body'=>$text]]);
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $resp = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);
  logNotification('whatsapp', $to, $text, $code>=200 && $code<300, $code>=200 && $code<300? null : ('HTTP ' . $code . ' ' . substr((string)$resp,0,200)));
}
function notifyNewOrder($orderId, $clientName, $phone, $email, $adresse, $paiement) {
  $sum = orderSummary($orderId);
  $subject = 'Nouvelle commande #' . $orderId;
  $vars = [
    'orderId'=>$orderId,
    'clientName'=>$clientName,
    'phone'=>$phone,
    'email'=>$email,
    'adresse'=>$adresse,
    'paiement'=>$paiement,
    'count'=>$sum['count'],
    'total'=>formatPrice($sum['total']),
    'items'=>implode("\n", $sum['lines'])
  ];
  $tpl = getSetting('tpl_admin_new_order', 'Commande #{{orderId}}\nClient: {{clientName}}\nTéléphone: {{phone}}\nEmail: {{email}}\nAdresse: {{adresse}}\nPaiement: {{paiement}}\nArticles: {{count}}\nTotal: {{total}}\n{{items}}');
  $body = renderTemplate($tpl, $vars);
  if (getSetting('notify_email_enabled','0') === '1') {
    $to = getSetting('notify_email_to');
    if ($to) sendEmailNative($to, $subject, $body);
    else logNotification('email', '-', $subject . "\n" . $body, false, 'no recipient configured');
  } else {
    logNotification('email', getSetting('notify_email_to','-'), $subject, false, 'channel disabled');
  }
  if (getSetting('notify_sms_enabled','0') === '1') {
    $to = getSetting('notify_sms_to');
    if ($to) sendSmsTwilio($to, $subject . ' | Total ' . formatPrice($sum['total']));
    else logNotification('sms', '-', $subject, false, 'no recipient configured');
  } else {
    logNotification('sms', getSetting('notify_sms_to','-'), $subject, false, 'channel disabled');
  }
  if (getSetting('notify_whatsapp_enabled','0') === '1') {
    $to = getSetting('notify_whatsapp_to');
    if ($to) sendWhatsAppGraph($to, $body);
    else logNotification('whatsapp', '-', $subject, false, 'no recipient configured');
  } else {
    logNotification('whatsapp', getSetting('notify_whatsapp_to','-'), $subject, false, 'channel disabled');
  }
}
function notifyClientOrderStatus($orderId, $status) {
  $pdo = db();
  $st = $pdo->prepare('SELECT commandes.id, clients.nom, clients.email, clients.telephone FROM commandes JOIN clients ON clients.id = commandes.id_client WHERE commandes.id = :id');
  $st->execute([':id'=>$orderId]);
  $c = $st->fetch();
  if (!$c) return;
  $sum = orderSummary($orderId);
  $labels = ['en_attente'=>'En attente','validee'=>'Validée','livree'=>'Livrée','annulee'=>'Annulée'];
  $subject = 'Commande #' . $orderId . ' - ' . (isset($labels[$status])? $labels[$status] : $status);
  $intro = '';
  if ($status === 'en_attente') { $intro = 'Votre commande a été reçue et est en attente de validation.'; }
  elseif ($status === 'validee') { $intro = 'Votre commande a été validée.'; }
  elseif ($status === 'livree') { $intro = 'Votre commande a été livrée. Merci pour votre achat !'; }
  elseif ($status === 'annulee') { $intro = 'Votre commande a été annulée. Pour toute question, contactez-nous.'; }
  else { $intro = 'Mise à jour de votre commande.'; }
  $vars = [
    'nom'=>$c['nom'],
    'orderId'=>$orderId,
    'status'=>$status,
    'statusLabel'=>isset($labels[$status])? $labels[$status]:$status,
    'intro'=>$intro,
    'total'=>formatPrice($sum['total']),
    'items'=>implode("\n", $sum['lines'])
  ];
  $tplKey = 'tpl_client_' . $status;
  $tplDefault = 'Bonjour {{nom}}\n{{intro}}\nCommande #{{orderId}}\nTotal: {{total}}\n{{items}}';
  $tpl = getSetting($tplKey, $tplDefault);
  $body = renderTemplate($tpl, $vars);
  if (getSetting('client_email_enabled','0') === '1' && !empty($c['email'])) {
    sendEmailNative($c['email'], $subject, $body);
  } else {
    logNotification('email_client', !empty($c['email'])? $c['email']:'-', $subject, false, !empty($c['email'])? 'channel disabled':'no email');
  }
  if (getSetting('client_sms_enabled','0') === '1' && !empty($c['telephone'])) {
    sendSmsTwilio($c['telephone'], $subject);
  } else {
    logNotification('sms_client', !empty($c['telephone'])? $c['telephone']:'-', $subject, false, !empty($c['telephone'])? 'channel disabled':'no phone');
  }
  if (getSetting('client_whatsapp_enabled','0') === '1' && !empty($c['telephone'])) {
    sendWhatsAppGraph($c['telephone'], $body);
  } else {
    logNotification('whatsapp_client', !empty($c['telephone'])? $c['telephone']:'-', $subject, false, !empty($c['telephone'])? 'channel disabled':'no phone');
  }
}
function logNotification($channel, $target, $content, $success, $error = null) {
  try {
    $st = db()->prepare('INSERT INTO notifications(channel,target,content,status,error) VALUES(:c,:t,:x,:s,:e)');
    $st->execute([':c'=>$channel, ':t'=>$target, ':x'=>$content, ':s'=>$success? 'success':'failed', ':e'=>$error]);
  } catch (Throwable $e) {}
}
function dbStatus() {
  try {
    $pdo = db();
    $ok = true;
    $counts = ['produits'=>0,'clients'=>0,'commandes'=>0];
    foreach ($counts as $k=>$_) {
      $stmt = $pdo->query('SELECT COUNT(*) AS c FROM ' . $k);
      $counts[$k] = (int)$stmt->fetch()['c'];
    }
    return ['ok'=>$ok, 'counts'=>$counts];
  } catch (Throwable $e) {
    return ['ok'=>false, 'error'=>$e->getMessage(), 'counts'=>['produits'=>0,'clients'=>0,'commandes'=>0]];
  }
}
function getCategories() {
  $stmt = db()->query("SELECT DISTINCT categorie FROM produits ORDER BY categorie");
  return $stmt->fetchAll();
}
function getFeaturedProducts($limit = 8) {
  $stmt = db()->prepare("SELECT * FROM produits ORDER BY id DESC LIMIT :lim");
  $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}
function getProducts($category = null, $page = 1, $perPage = 12, $search = null) {
  $offset = ($page - 1) * $perPage;
  $where = [];
  $params = [];
  if ($category) { $where[] = 'categorie = :cat'; $params[':cat'] = $category; }
  if ($search) { $where[] = '(nom LIKE :q OR description LIKE :q)'; $params[':q'] = '%' . $search . '%'; }
  $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM produits' . (count($where)? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT :lim OFFSET :off';
  $stmt = db()->prepare($sql);
  foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
  $stmt->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
  $stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
  $stmt->execute();
  $items = $stmt->fetchAll();
  $total = (int)db()->query('SELECT FOUND_ROWS() AS t')->fetch()['t'];
  return [$items, $total];
}
function getProductById($id) {
  $stmt = db()->prepare("SELECT * FROM produits WHERE id = :id");
  $stmt->execute([':id' => $id]);
  return $stmt->fetch();
}
function ensureSession() { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); }
function cartGet() { ensureSession(); return isset($_SESSION['cart']) ? $_SESSION['cart'] : []; }
function cartSave($cart) { ensureSession(); $_SESSION['cart'] = $cart; }
function cartAdd($productId, $qty = 1) {
  $cart = cartGet();
  $pid = (int)$productId;
  $q = (int)$qty;
  if ($q < 1) $q = 1;
  $cart[$pid] = isset($cart[$pid]) ? $cart[$pid] + $q : $q;
  cartSave($cart);
}
function cartUpdate($productId, $qty) {
  $cart = cartGet();
  $pid = (int)$productId;
  $q = (int)$qty;
  if ($q <= 0) { unset($cart[$pid]); } else { $cart[$pid] = $q; }
  cartSave($cart);
}
function cartRemove($productId) {
  $cart = cartGet();
  $pid = (int)$productId;
  if (isset($cart[$pid])) unset($cart[$pid]);
  cartSave($cart);
}
function cartTotal() {
  $cart = cartGet();
  $sum = 0;
  if (!$cart) return 0;
  $ids = implode(',', array_map('intval', array_keys($cart)));
  if (!$ids) return 0;
  $rows = db()->query("SELECT id, prix FROM produits WHERE id IN ($ids)")->fetchAll();
  foreach ($rows as $r) { $sum += ((float)$r['prix']) * (int)$cart[(int)$r['id']]; }
  return $sum;
}
function adminLoggedIn() { ensureSession(); return isset($_SESSION['admin']) && $_SESSION['admin'] === true; }
function requireAdmin() { if (!adminLoggedIn()) { header('Location: ' . base('admin/login.php')); exit; } }