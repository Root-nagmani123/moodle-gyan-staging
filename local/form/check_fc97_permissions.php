<?php
// /local/form/check_fc97_permissions.php

require_once('../../config.php');

$LDAPServer = "103.225.204.25";
$loginDN = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
$bind_password = "lbsnaa123";
$container = "OU=FC97,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in";

echo "<h2>Check FC97 OU Permissions</h2>";

$ldapconn = ldap_connect($LDAPServer, 389);
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

$ldapbind = @ldap_bind($ldapconn, $loginDN, $bind_password);
if (!$ldapbind) {
    die("Bind failed: " . ldap_error($ldapconn));
}

echo "✓ Connected and bound<br><br>";

// Check if we can read the OU
echo "<strong>Checking if OU exists and is readable:</strong><br>";
$search = @ldap_read($ldapconn, $container, "(objectClass=*)", ['ou', 'objectClass']);
if ($search) {
    $entries = ldap_get_entries($ldapconn, $search);
    echo "✓ OU exists and is readable<br>";
    echo "OU Name: " . $entries[0]['ou'][0] . "<br>";
    echo "ObjectClass: " . implode(', ', $entries[0]['objectclass']) . "<br><br>";
} else {
    echo "✗ Cannot read OU: " . ldap_error($ldapconn) . "<br><br>";
}

// Check if we can create a test entry
echo "<strong>Testing write permissions:</strong><br>";
$test_dn = "cn=test_permission_check,{$container}";
$test_data = [
    'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
    'cn' => 'test_permission_check',
    'sn' => 'Check',
    'sAMAccountName' => 'test_permission_check',
    'userAccountControl' => '514'  // Disabled account for test
];

$result = @ldap_add($ldapconn, $test_dn, $test_data);
if ($result) {
    echo "✓ Can create test entry<br>";
    ldap_delete($ldapconn, $test_dn);
    echo "✓ Test entry deleted<br>";
    echo "<strong>✅ Bind user HAS write permissions on FC97 OU!</strong>";
} else {
    $errno = ldap_errno($ldapconn);
    echo "✗ Cannot create test entry: Error {$errno}: " . ldap_error($ldapconn) . "<br>";
    if ($errno == 50) {
        echo "<strong>❌ Bind user lacks write permissions on FC97 OU</strong><br>";
        echo "Need to grant 'Create User objects' permission to: {$loginDN}<br>";
        echo "on OU: {$container}";
    } elseif ($errno == 53) {
        echo "<strong>⚠️ The OU may have restrictions preventing user creation</strong><br>";
        echo "Check if user creation is allowed in this OU";
    }
}

ldap_close($ldapconn);
?>