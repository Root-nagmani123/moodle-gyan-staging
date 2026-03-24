<?php
// /local/form/test_pps1_account.php

require_once('../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$LDAPServer = "103.225.204.25";
$container = "CN=Users,DC=lbsnaa,DC=gov,DC=in";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test pps1 Account for User Creation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 1000px; margin: 0 auto; background: #252526; padding: 20px; border-radius: 8px; }
        .success { color: #4ec9b0; font-weight: bold; }
        .error { color: #f48771; font-weight: bold; }
        .info { color: #9cdcfe; }
        pre { background: #2d2d2d; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { background: #0e639c; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔐 Testing pps1 Account for User Creation</h1>";

// First, find pps1 DN
$ldapconn = ldap_connect($LDAPServer, 389);
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

// Bind with pps1 account
$pps1_username = "pps1";
$pps1_password = "@#India2018";

echo "<h3>Step 1: Finding pps1 DN</h3>";

// First bind with admin to find pps1
$admin_dn = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
$admin_password = "lbsnaa123";

$admin_bind = @ldap_bind($ldapconn, $admin_dn, $admin_password);
if ($admin_bind) {
    $search = @ldap_search($ldapconn, $container, "(sAMAccountName=pps1)", ['dn', 'cn']);
    if ($search) {
        $entries = ldap_get_entries($ldapconn, $search);
        if ($entries['count'] > 0) {
            $pps1_dn = $entries[0]['dn'];
            echo "<div class='success'>✓ Found pps1: {$pps1_dn}</div>";
        } else {
            echo "<div class='error'>✗ pps1 not found</div>";
            $pps1_dn = "cn=pps1,{$container}";
        }
    } else {
        $pps1_dn = "cn=pps1,{$container}";
    }
} else {
    $pps1_dn = "cn=pps1,{$container}";
}

echo "<br><h3>Step 2: Testing pps1 Authentication</h3>";

// Test binding with pps1
$pps1_bind = @ldap_bind($ldapconn, $pps1_dn, $pps1_password);

if (!$pps1_bind) {
    echo "<div class='error'>✗ Cannot authenticate with pps1 account</div>";
    echo "<div class='info'>Error: " . ldap_error($ldapconn) . "</div>";
    echo "<div class='info'>Please verify the password: @#India2018</div>";
} else {
    echo "<div class='success'>✓ Successfully authenticated as pps1!</div><br>";
    
    // Test user creation with pps1
    $test_username = "testuser_" . time();
    $test_password = "Admin@12345678";
    $test_firstname = "Test";
    $test_lastname = "User";
    $givenname = $test_firstname . " " . $test_lastname;
    $dn = "cn={$givenname},{$container}";
    
    echo "<h3>Step 3: Testing User Creation with pps1</h3>";
    echo "Attempting to create user: {$test_username}<br>";
    echo "DN: {$dn}<br><br>";
    
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
        echo "<div class='success'>✅ SUCCESS! User created with pps1 account!</div>";
        echo "User: {$test_username}<br>";
        
        // Enable account
        ldap_modify($ldapconn, $dn, ['userAccountControl' => '512']);
        echo "✓ Account enabled<br>";
        
        // Clean up
        ldap_delete($ldapconn, $dn);
        echo "✓ Test user deleted.<br>";
        echo "<div class='success'>🎉 pps1 account works for user creation! Use it in your code.</div>";
        
        // ==================== WORKING CODE ====================
        echo "<hr>";
        echo "<h2>📝 Working Registration Code (Using pps1)</h2>";
        echo "<pre>
function local_form_create_ad_user(\$username, \$firstname, \$lastname, \$password, \$email, \$phone) {
    
    // Use pps1 as the service account
    \$LDAPServer = \"103.225.204.25\";
    \$loginDN = \"cn=pps1,CN=Users,DC=lbsnaa,DC=gov,DC=in\";
    \$bind_password = \"@#India2018\";
    
    // Working container
    \$container = \"CN=Users,DC=lbsnaa,DC=gov,DC=in\";
    
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
        $errno = ldap_errno($ldapconn);
        $error = ldap_error($ldapconn);
        echo "<div class='error'>✗ Failed to create user with pps1: Error {$errno}: {$error}</div>";
        
        if ($errno == 50) {
            echo "<div class='info'>pps1 account lacks 'Create User objects' permission.</div>";
            echo "<div class='info'>Please ask AD admin to grant this permission to pps1 on the CN=Users container.</div>";
        } elseif ($errno == 53) {
            echo "<div class='info'>Check if the container exists and has proper permissions.</div>";
        }
    }
}

ldap_close($ldapconn);
echo "</div></body></html>";
?>