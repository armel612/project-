<?php
// Ticket_inventory-system/debug_api.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Eneo API Debugger</h3>";

try {
    echo "1. Checking include files...<br>";
    require_once 'routes/api.php';
    echo " - OK<br>";
    
    echo "2. Initializing API...<br>";
    $api = new API();
    echo " - OK<br>";
    
    echo "3. Checking Database connectivity...<br>";
    $db = getDB();
    $q = $db->query("SELECT COUNT(*) FROM users");
    echo " - OK (Users count: " . $q->fetchColumn() . ")<br>";

    echo "4. Checking Roles table...<br>";
    $q = $db->query("SELECT COUNT(*) FROM roles");
    echo " - OK (Roles count: " . $q->fetchColumn() . ")<br>";

    echo "<br><b style='color:green'>System appears healthy!</b> If you still see 'Backend Error', please check your Browser Console for specific JSON parsing issues.";

} catch (Throwable $e) {
    echo "<br><b style='color:red'>FAILURE DETECTED:</b><br>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")";
    echo "<br><br>Please run <b>setup.php</b> to fix missing tables.";
}
