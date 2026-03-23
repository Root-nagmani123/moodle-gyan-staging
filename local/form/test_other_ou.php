<?php
// /local/form/test_other_ou.php

require_once('../../config.php');

$test_username = "testpermission_" . time();
$test_password = "Admin@123456";
$test_firstname = "Test";
$test_lastname = "User";

// Test different OUs
$ous_to_test = [
    "OU=Test-Ad,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in",
    "OU=STAFF,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in",
    "OU=Project,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in",
    "OU=ALUMNI,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in"
];

echo "<h2>Test Creating User in Different OUs</h2>";

$LDAPServer = "103.225.204.25";
$loginDN = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
$bind_password = "lbsnaa123";

$ldapconn = ldap_connect($LDAPServer, 389);
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

$ldapbind = @ldap_bind($ldapconn, $loginDN, $bind_password);
if (!$ldapbind) {
    die("Bind failed: " . ldap_error($ldapconn));
}

echo "✓ Connected and bound successfully<br><br>";

foreach ($ous_to_test as $ou) {
    echo "<h3>Testing OU: {$ou}</h3>";
    
    $givenname = $test_firstname . " " . $test_lastname;
    $dn = "cn={$givenname},{$ou}";
    
    // Check if OU exists
    $check = @ldap_read($ldapconn, $ou, "(objectClass=*)", ['ou']);
    if (!$check) {
        echo "✗ OU does not exist or is not accessible: " . ldap_error($ldapconn) . "<br><br>";
        continue;
    }
    
    // Try to create minimal user
    $newpass = "\"" . $test_password . "\"";
    $newUnicodepass = mb_convert_encoding($newpass, 'UTF-16LE', 'UTF-8');
    
    $userdata = [
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
        'cn' => $givenname,
        'sn' => $test_lastname,
        'sAMAccountName' => $test_username,
        'userPrincipalName' => "{$test_username}@lbsnaa.gov.in",
        'userAccountControl' => '544',
        'unicodePwd' => $newUnicodepass,
        'instanceType' => '4'
    ];
    
    $result = @ldap_add($ldapconn, $dn, $userdata);
    
    if ($result) {
        echo "<div style='color:green;font-weight:bold'>✓ SUCCESS! Can create user in this OU!</div>";
        // Clean up
        ldap_delete($ldapconn, $dn);
        echo "✓ Test user deleted<br>";
        break;
    } else {
        $errno = ldap_errno($ldapconn);
        echo "<div style='color:red'>✗ Cannot create user: Error {$errno} - " . ldap_error($ldapconn) . "</div>";
    }
    echo "<br>";
}

ldap_close($ldapconn);
?>