<?php
// /local/form/final_ad_test.php

require_once('../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$LDAPServer = "103.225.204.25";
$loginDN = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
$bind_password = "lbsnaa123";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Final AD Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 1200px; margin: 0 auto; background: #252526; padding: 20px; border-radius: 8px; }
        .success { color: #4ec9b0; font-weight: bold; }
        .error { color: #f48771; font-weight: bold; }
        .info { color: #9cdcfe; }
        pre { background: #2d2d2d; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { background: #0e639c; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #2d2d2d; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🎯 Final AD Test - Finding Working Container</h1>";

$ldapconn = ldap_connect($LDAPServer, 389);
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

$ldapbind = @ldap_bind($ldapconn, $loginDN, $bind_password);
if (!$ldapbind) {
    die("<div class='error'>Bind failed: " . ldap_error($ldapconn) . "</div></div></body></html>");
}

echo "<div class='success'>✓ Connected and bound successfully</div><br>";

// Containers to test (from your actual LDAP structure)
$containers_to_test = [
    'CN=Users,DC=lbsnaa,DC=gov,DC=in' => 'Default Users Container',
    'OU=FC97,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in' => 'Java FC97 Container',
    'OU=Test-Ad,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in' => 'Test-Ad Container',
    'OU=STAFF,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in' => 'STAFF Container',
];

$test_username = "testuser_" . time();
$test_password = "Admin@12345678";
$test_firstname = "Test";
$test_lastname = "User";
$test_phone = "9999999999";
$test_email = "test@test.com";

echo "<h2>Testing User Creation in Different Containers</h2>";
echo "<table>";
echo "<tr><th>Container</th><th>Status</th><th>Result</th></tr>";

$working_container = null;

foreach ($containers_to_test as $container => $description) {
    echo "<tr><td><strong>{$description}</strong><br><code>{$container}</code></td>";
    
    // Check if container exists
    $check = @ldap_read($ldapconn, $container, "(objectClass=*)", ['ou', 'cn']);
    if (!$check) {
        echo "<td class='error'>Does not exist</td><td>-</td></tr>";
        continue;
    }
    
    echo "<td class='info'>Exists</td>";
    
    // Try to create user with Java logic
    $givenname = $test_firstname . " " . $test_lastname;
    $dn = "cn={$givenname},{$container}";
    
    // Encode password
    $newpass = "\"" . $test_password . "\"";
    $newUnicodepass = mb_convert_encoding($newpass, 'UTF-16LE', 'UTF-8');
    
    $userdata = [
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
        'cn' => $givenname,
        'sn' => $test_lastname,
        'givenName' => $test_firstname,
        'displayName' => $givenname,
        'sAMAccountName' => $test_username,
        'userPrincipalName' => "{$test_username}@lbsnaa.gov.in",
        'userAccountControl' => '544',
        'pwdLastSet' => '-1',
        'unicodePwd' => $newUnicodepass,
        'instanceType' => '4',
        'accountExpires' => '0'
    ];
    
    $result = @ldap_add($ldapconn, $dn, $userdata);
    
    if ($result) {
        echo "<td class='success'>✅ SUCCESS! User created!</td></tr>";
        $working_container = $container;
        // Clean up
        ldap_delete($ldapconn, $dn);
        echo "<tr><td colspan='3' class='success'>✓ Test user deleted. Container works!</td></tr>";
        break;
    } else {
        $errno = ldap_errno($ldapconn);
        echo "<td class='error'>✗ Failed: Error {$errno}</td></tr>";
    }
}

if (!$working_container) {
    echo "<tr><td colspan='3' class='error'>❌ No working container found. Need AD admin to grant permissions.</td></tr>";
}

// ==================== WORKING CODE ====================
echo "<hr>";
echo "<h2>📝 Working Registration Code</h2>";

if ($working_container) {
    echo "<div class='success'>✅ Working container found: <code>{$working_container}</code></div>";
    echo "<pre>
function local_form_create_ad_user(\$username, \$firstname, \$lastname, \$password, \$email, \$phone) {
    
    \$LDAPServer = \"103.225.204.25\";
    \$loginDN = \"cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in\";
    \$bind_password = \"lbsnaa123\";
    
    // WORKING CONTAINER
    \$container = \"" . addslashes($working_container) . "\";
    
    // Java style: CN = Firstname + Lastname
    \$givenname = \$firstname . \" \" . \$lastname;
    \$dn = \"cn={\$givenname},{\$container}\";
    
    \$ldapconn = ldap_connect(\$LDAPServer, 389);
    ldap_set_option(\$ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option(\$ldapconn, LDAP_OPT_REFERRALS, 0);
    
    \$ldapbind = @ldap_bind(\$ldapconn, \$loginDN, \$bind_password);
    if (!\$ldapbind) {
        error_log(\"LDAP bind failed: \" . ldap_error(\$ldapconn));
        return false;
    }
    
    // Password encoding (Java style)
    \$newpass = \"\\\"\" . \$password . \"\\\"\";
    \$newUnicodepass = mb_convert_encoding(\$newpass, 'UTF-16LE', 'UTF-8');
    
    \$userdata = [
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
        'cn' => \$givenname,
        'sn' => \$lastname,
        'givenName' => \$firstname,
        'displayName' => \$givenname,
        'sAMAccountName' => \$username,
        'userPrincipalName' => \"{\$username}@lbsnaa.gov.in\",
        'userAccountControl' => '544',
        'pwdLastSet' => '-1',
        'unicodePwd' => \$newUnicodepass,
        'instanceType' => '4',
        'accountExpires' => '0',
        'mail' => \$email,
        'telephoneNumber' => \$phone
    ];
    
    \$result = @ldap_add(\$ldapconn, \$dn, \$userdata);
    
    if (\$result) {
        // Enable account
        ldap_modify(\$ldapconn, \$dn, ['userAccountControl' => '512']);
        error_log(\"AD user created: {\$username}\");
        ldap_close(\$ldapconn);
        return true;
    }
    
    error_log(\"Failed to create AD user: \" . ldap_error(\$ldapconn));
    ldap_close(\$ldapconn);
    return false;
}
</pre>";
} else {
    echo "<div class='error'>No working container found. Please contact AD administrator to grant permissions.</div>";
    echo "<div class='info'>The bind user <code>{$loginDN}</code> needs 'Create User objects' permission on one of these containers:</div>";
    echo "<ul>";
    foreach ($containers_to_test as $container => $desc) {
        echo "<li><code>{$container}</code> - {$desc}</li>";
    }
    echo "</ul>";
}

ldap_close($ldapconn);
echo "</div></body></html>";
?>