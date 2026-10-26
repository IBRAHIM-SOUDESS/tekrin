<?php
return [
  'db_host' => getenv('DB_HOST') ?: 'localhost',
  'db_name' => getenv('DB_NAME') ?: 'ets_kamal',
  'db_user' => getenv('DB_USER') ?: 'root',
  'db_pass' => getenv('DB_PASS') ?: '',
  'base_url' => getenv('BASE_URL') ?: '/entreprise',
  'admin_user' => getenv('ADMIN_USER') ?: 'admin@etskamal.cm',
  'admin_pass' => getenv('ADMIN_PASS') ?: 'admin123'
];