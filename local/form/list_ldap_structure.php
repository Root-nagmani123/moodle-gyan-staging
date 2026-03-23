<?php
// /local/form/list_ldap_structure.php

require_once('../../config.php');

echo "<h2>LDAP Directory Structure</h2>";

$ldap_server = "103.225.204.25";
$ldap_port = 389;
$bind_dn = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
$bind_password = "lbsnaa123";

$ldapconn = ldap_connect($ldap_server, $ldap_port);
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

$ldapbind = @ldap_bind($ldapconn, $bind_dn, $bind_password);
if (!$ldapbind) {
    die("Bind failed: " . ldap_error($ldapconn));
}

echo "<div class='alert alert-success'>✓ Connected and bound successfully</div><br>";

// 1. List all Organizational Units (OUs)
echo "<h3>1. Organizational Units (OUs):</h3>";
$search_filter = "(objectClass=organizationalUnit)";
$search_result = @ldap_search($ldapconn, "dc=lbsnaa,dc=gov,dc=in", $search_filter, ['ou', 'dn'], 0, 100);

if ($search_result) {
    $entries = ldap_get_entries($ldapconn, $search_result);
    echo "Found " . $entries['count'] . " OUs:<br>";
    echo "<ul>";
    for ($i = 0; $i < $entries['count']; $i++) {
        $ou = isset($entries[$i]['ou'][0]) ? $entries[$i]['ou'][0] : 'N/A';
        $dn = $entries[$i]['dn'];
        echo "<li><strong>{$ou}</strong> - DN: {$dn}</li>";
    }
    echo "</ul>";
} else {
    echo "No OUs found<br>";
}

// 2. List all Containers
echo "<h3>2. Containers:</h3>";
$search_filter = "(objectClass=container)";
$search_result = @ldap_search($ldapconn, "dc=lbsnaa,dc=gov,dc=in", $search_filter, ['cn', 'dn'], 0, 100);

if ($search_result) {
    $entries = ldap_get_entries($ldapconn, $search_result);
    echo "Found " . $entries['count'] . " containers:<br>";
    echo "<ul>";
    for ($i = 0; $i < $entries['count']; $i++) {
        $cn = isset($entries[$i]['cn'][0]) ? $entries[$i]['cn'][0] : 'N/A';
        $dn = $entries[$i]['dn'];
        echo "<li><strong>{$cn}</strong> - DN: {$dn}</li>";
    }
    echo "</ul>";
} else {
    echo "No containers found<br>";
}

// 3. List all top-level entries
echo "<h3>3. Top-level entries under dc=lbsnaa,dc=gov,dc=in:</h3>";
$search_filter = "(objectClass=*)";
$search_result = @ldap_list($ldapconn, "dc=lbsnaa,dc=gov,dc=in", $search_filter, ['dn', 'objectClass'], 0, 100);

if ($search_result) {
    $entries = ldap_get_entries($ldapconn, $search_result);
    echo "Found " . $entries['count'] . " entries:<br>";
    echo "<ul>";
    for ($i = 0; $i < $entries['count']; $i++) {
        $dn = $entries[$i]['dn'];
        $objectclass = isset($entries[$i]['objectclass']) ? implode(', ', $entries[$i]['objectclass']) : 'N/A';
        echo "<li><strong>{$dn}</strong> - Type: {$objectclass}</li>";
    }
    echo "</ul>";
} else {
    echo "No entries found<br>";
}

ldap_close($ldapconn);
?>