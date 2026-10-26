<?php require_once __DIR__ . '/../includes/functions.php'; requireAdmin(); ?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $gp = isset($_POST['gallery_public']) ? '1' : '0';
  setSetting('gallery_public', $gp);
  setSetting('notify_email_enabled', isset($_POST['notify_email_enabled']) ? '1' : '0');
  setSetting('notify_email_to', trim($_POST['notify_email_to'] ?? ''));
  setSetting('notify_sms_enabled', isset($_POST['notify_sms_enabled']) ? '1' : '0');
  setSetting('notify_sms_to', trim($_POST['notify_sms_to'] ?? ''));
  setSetting('twilio_sid', trim($_POST['twilio_sid'] ?? ''));
  setSetting('twilio_token', trim($_POST['twilio_token'] ?? ''));
  setSetting('twilio_from', trim($_POST['twilio_from'] ?? ''));
  setSetting('notify_whatsapp_enabled', isset($_POST['notify_whatsapp_enabled']) ? '1' : '0');
  setSetting('notify_whatsapp_to', trim($_POST['notify_whatsapp_to'] ?? ''));
  setSetting('whatsapp_token', trim($_POST['whatsapp_token'] ?? ''));
  setSetting('whatsapp_phone_id', trim($_POST['whatsapp_phone_id'] ?? ''));
  setSetting('client_email_enabled', isset($_POST['client_email_enabled']) ? '1' : '0');
  setSetting('client_sms_enabled', isset($_POST['client_sms_enabled']) ? '1' : '0');
  setSetting('client_whatsapp_enabled', isset($_POST['client_whatsapp_enabled']) ? '1' : '0');
  setSetting('tpl_admin_new_order', trim($_POST['tpl_admin_new_order'] ?? ''));
  setSetting('tpl_client_en_attente', trim($_POST['tpl_client_en_attente'] ?? ''));
  setSetting('tpl_client_validee', trim($_POST['tpl_client_validee'] ?? ''));
  setSetting('tpl_client_livree', trim($_POST['tpl_client_livree'] ?? ''));
  setSetting('tpl_client_annulee', trim($_POST['tpl_client_annulee'] ?? ''));
  setSetting('smtp_host', trim($_POST['smtp_host'] ?? ''));
  setSetting('smtp_port', trim($_POST['smtp_port'] ?? ''));
  setSetting('smtp_from', trim($_POST['smtp_from'] ?? ''));
  setSetting('smtp_from_name', trim($_POST['smtp_from_name'] ?? ''));
  setSetting('smtp_user', trim($_POST['smtp_user'] ?? ''));
  setSetting('smtp_pass', trim($_POST['smtp_pass'] ?? ''));
  setSetting('smtp_secure', trim($_POST['smtp_secure'] ?? ''));
  header('Location: ' . base('admin/settings.php'));
  exit;
}
$enabled = galleryPublicEnabled();
$notify_email_enabled = getSetting('notify_email_enabled','0')==='1';
$notify_email_to = getSetting('notify_email_to','');
$notify_sms_enabled = getSetting('notify_sms_enabled','0')==='1';
$notify_sms_to = getSetting('notify_sms_to','');
$twilio_sid = getSetting('twilio_sid','');
$twilio_token = getSetting('twilio_token','');
$twilio_from = getSetting('twilio_from','');
$notify_whatsapp_enabled = getSetting('notify_whatsapp_enabled','0')==='1';
$notify_whatsapp_to = getSetting('notify_whatsapp_to','');
$whatsapp_token = getSetting('whatsapp_token','');
$whatsapp_phone_id = getSetting('whatsapp_phone_id','');
$client_email_enabled = getSetting('client_email_enabled','0')==='1';
$client_sms_enabled = getSetting('client_sms_enabled','0')==='1';
$client_whatsapp_enabled = getSetting('client_whatsapp_enabled','0')==='1';
$tpl_admin_new_order = getSetting('tpl_admin_new_order','');
$tpl_client_en_attente = getSetting('tpl_client_en_attente','');
$tpl_client_validee = getSetting('tpl_client_validee','');
$tpl_client_livree = getSetting('tpl_client_livree','');
$tpl_client_annulee = getSetting('tpl_client_annulee','');
$smtp_host = getSetting('smtp_host','');
$smtp_port = getSetting('smtp_port','');
$smtp_from = getSetting('smtp_from','');
$smtp_from_name = getSetting('smtp_from_name','');
$smtp_user = getSetting('smtp_user','');
$smtp_pass = getSetting('smtp_pass','');
$smtp_secure = getSetting('smtp_secure','');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section>
  <div class="admin-top">
    <h2>Réglages</h2>
    <a class="btn" href="<?php echo base('admin/index.php'); ?>">Retour</a>
  </div>
  <form method="post" class="form" style="max-width:480px">
    <?php echo csrfField(); ?>
    <label style="display:flex;align-items:center;gap:8px">
      <input type="checkbox" name="gallery_public" <?php echo $enabled? 'checked':''; ?>>
      <span>Afficher la galerie au public</span>
    </label>
    <h3>Notifications</h3>
    <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="notify_email_enabled" <?php echo $notify_email_enabled? 'checked':''; ?>><span>Activer email</span></label>
    <input name="notify_email_to" placeholder="Email destinataire" value="<?php echo sanitize($notify_email_to); ?>">
    <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="notify_sms_enabled" <?php echo $notify_sms_enabled? 'checked':''; ?>><span>Activer SMS (Twilio)</span></label>
    <input name="notify_sms_to" placeholder="Téléphone destinataire (format international)" value="<?php echo sanitize($notify_sms_to); ?>">
    <input name="twilio_sid" placeholder="Twilio SID" value="<?php echo sanitize($twilio_sid); ?>">
    <input name="twilio_token" placeholder="Twilio Token" value="<?php echo sanitize($twilio_token); ?>">
    <input name="twilio_from" placeholder="Twilio From (numéro émetteur)" value="<?php echo sanitize($twilio_from); ?>">
    <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="notify_whatsapp_enabled" <?php echo $notify_whatsapp_enabled? 'checked':''; ?>><span>Activer WhatsApp</span></label>
    <input name="notify_whatsapp_to" placeholder="WhatsApp destinataire (format international)" value="<?php echo sanitize($notify_whatsapp_to); ?>">
    <input name="whatsapp_token" placeholder="WhatsApp Token API" value="<?php echo sanitize($whatsapp_token); ?>">
    <input name="whatsapp_phone_id" placeholder="WhatsApp Phone ID" value="<?php echo sanitize($whatsapp_phone_id); ?>">
    <h3>Notifications client (statut de commande)</h3>
    <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="client_email_enabled" <?php echo $client_email_enabled? 'checked':''; ?>><span>Email client</span></label>
    <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="client_sms_enabled" <?php echo $client_sms_enabled? 'checked':''; ?>><span>SMS client (Twilio)</span></label>
    <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="client_whatsapp_enabled" <?php echo $client_whatsapp_enabled? 'checked':''; ?>><span>WhatsApp client</span></label>
    <h3>Modèles de notifications</h3>
    <label>Admin — nouvelle commande</label>
    <textarea name="tpl_admin_new_order" rows="4" placeholder="Commande #{{orderId}}\nClient: {{clientName}}\nTotal: {{total}}\n{{items}}" style="width:100%"><?php echo sanitize($tpl_admin_new_order); ?></textarea>
    <label>Client — en attente</label>
    <textarea name="tpl_client_en_attente" rows="3" placeholder="Bonjour {{nom}}\nVotre commande est en attente de validation.\nCommande #{{orderId}}\nTotal: {{total}}" style="width:100%"><?php echo sanitize($tpl_client_en_attente); ?></textarea>
    <label>Client — validée</label>
    <textarea name="tpl_client_validee" rows="3" placeholder="Bonjour {{nom}}\nVotre commande a été validée.\nCommande #{{orderId}}\nTotal: {{total}}" style="width:100%"><?php echo sanitize($tpl_client_validee); ?></textarea>
    <label>Client — livrée</label>
    <textarea name="tpl_client_livree" rows="3" placeholder="Bonjour {{nom}}\nVotre commande a été livrée. Merci !\nCommande #{{orderId}}\nTotal: {{total}}" style="width:100%"><?php echo sanitize($tpl_client_livree); ?></textarea>
    <label>Client — annulée</label>
    <textarea name="tpl_client_annulee" rows="3" placeholder="Bonjour {{nom}}\nVotre commande a été annulée.\nCommande #{{orderId}}\nTotal: {{total}}" style="width:100%"><?php echo sanitize($tpl_client_annulee); ?></textarea>
    <h3>Email SMTP (WAMP)</h3>
    <input name="smtp_host" placeholder="SMTP host (ex: smtp.example.com)" value="<?php echo sanitize($smtp_host); ?>">
    <input name="smtp_port" placeholder="SMTP port (ex: 25 ou 587)" value="<?php echo sanitize($smtp_port); ?>">
    <input name="smtp_from" placeholder="From (email expéditeur)" value="<?php echo sanitize($smtp_from); ?>">
    <input name="smtp_from_name" placeholder="From (nom expéditeur)" value="<?php echo sanitize($smtp_from_name); ?>">
    <input name="smtp_user" placeholder="SMTP utilisateur" value="<?php echo sanitize($smtp_user); ?>">
    <input name="smtp_pass" type="password" placeholder="SMTP mot de passe" value="<?php echo sanitize($smtp_pass); ?>">
    <input name="smtp_secure" placeholder="Sécurité (tls, ssl, none)" value="<?php echo sanitize($smtp_secure); ?>">
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Enregistrer</button>
    </div>
  </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>