<?php
require_once 'functions.php';

echo "PHP TIME: " . date('Y-m-d H:i:s') . "\n";
$mysql = $db->query("SELECT NOW() as n")->fetch();
echo "MYSQL TIME: " . $mysql['n'] . "\n";

$appts = $db->query("SELECT id, appointment_date, appointment_time FROM appointments ORDER BY id DESC LIMIT 5")->fetchAll();
echo "LAST 5 APPOINTMENTS:\n";
foreach($appts as $a) {
    echo "ID: {$a['id']} | Date: {$a['appointment_date']} | Time: {$a['appointment_time']}\n";
}
?>
