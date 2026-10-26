<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = db();
$schemaPath = __DIR__ . '/../database/schema.sql';
if (file_exists($schemaPath)) {
  $sql = file_get_contents($schemaPath);
  $stmts = array_filter(array_map('trim', explode(';', $sql)));
  foreach ($stmts as $s) { if ($s !== '') { $pdo->exec($s); } }
}
$count = (int)$pdo->query('SELECT COUNT(*) AS c FROM produits')->fetch()['c'];
if ($count > 0) { echo "Produits déjà présents: $count\n"; exit(0); }
$items = [
  ['nom'=>'Ordinateur Portable HP 15s','description'=>'Intel i5, 8Go RAM, 256Go SSD','prix'=>350000,'image'=>'https://placehold.co/640x360?text=HP+15s','categorie'=>'Ordinateurs'],
  ['nom'=>'Lenovo ThinkPad E14','description'=>'i5 12e, 16Go RAM, 512Go SSD','prix'=>600000,'image'=>'https://placehold.co/640x360?text=ThinkPad+E14','categorie'=>'Ordinateurs'],
  ['nom'=>'Imprimante HP LaserJet Pro','description'=>'Imprimante laser noir et blanc','prix'=>180000,'image'=>'https://placehold.co/640x360?text=HP+LaserJet','categorie'=>'Imprimantes'],
  ['nom'=>'Canon PIXMA Couleur','description'=>'Jet d’encre couleur, Wi‑Fi','prix'=>120000,'image'=>'https://placehold.co/640x360?text=Canon+PIXMA','categorie'=>'Imprimantes'],
  ['nom'=>'Souris Logitech M185','description'=>'Sans fil, ergonomique','prix'=>10000,'image'=>'https://placehold.co/640x360?text=Logitech+M185','categorie'=>'Accessoires'],
  ['nom'=>'Clavier HP USB','description'=>'Clavier filaire AZERTY','prix'=>9000,'image'=>'https://placehold.co/640x360?text=Clavier+HP','categorie'=>'Accessoires'],
  ['nom'=>'Disque SSD 1To','description'=>'NVMe Gen4, 5000MB/s','prix'=>150000,'image'=>'https://placehold.co/640x360?text=SSD+1To','categorie'=>'Stockage'],
  ['nom'=>'Onduleur 1200VA','description'=>'Protection pour PC et imprimante','prix'=>80000,'image'=>'https://placehold.co/640x360?text=Onduleur+1200VA','categorie'=>'Énergie']
];
$st = $pdo->prepare('INSERT INTO produits(nom,description,prix,image,categorie) VALUES(:n,:d,:p,:i,:c)');
foreach ($items as $it) { $st->execute([':n'=>$it['nom'], ':d'=>$it['description'], ':p'=>$it['prix'], ':i'=>$it['image'], ':c'=>$it['categorie']]); }
echo "Produits insérés: " . count($items) . "\n";