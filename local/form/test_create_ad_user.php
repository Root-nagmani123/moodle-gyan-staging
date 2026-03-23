<?php
// /local/form/test_create_ad_user_fc97.php

require_once('../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$test_username = optional_param('username', 'testuser10', PARAM_TEXT);
$test_password = optional_param('password', 'Admin@123', PARAM_TEXT);
$test_firstname = optional_param('firstname', 'Test', PARAM_TEXT);
$test_lastname = optional_param('lastname', 'User10', PARAM_TEXT);
$test_phone = optional_param('phone', '9999999999', PARAM_TEXT);
$test_email = optional_param('email', 'test10@test.com', PARAM_TEXT);

echo "<h2>Test Creating AD User in FC97 OU</h2>";

echo "<form method='post'>";
echo "<table border='0'>";
echo "<tr><td>Username:</td><td><input type='text' name='username' value='{$test_username}' size='30'></td></tr>";
echo "<tr><td>First Name:</td><td><input type='text' name='firstname' value='{$test_firstname}' size='30'></td></tr>";
echo "<tr><td>Last Name:</td><td><input type='text' name='lastname' value='{$test_lastname}' size='30'></td></tr>";
echo "<tr><td>Password:</td><td><input type='password' name='password' value='{$test_password}' size='30'></td></tr>";
echo "<tr><td>Email:</td><td><input type='text' name='email' value='{$test_email}' size='30'></td></tr>";
echo "<tr><td>Phone:</td><td><input type='text' name='phone' value='{$test_phone}' size='30'></td></tr>";
echo "<tr><td></td><td><input type='submit' value='Test Create User in FC97' style='background:#4CAF50;color:white;padding:10px;border:none;'></td></tr>";
echo "</table>";
echo "</form><br><hr><br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $LDAPServer = "103.225.204.25";
    $LDAPPORT = 389;
    $loginDN = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
    $bind_password = "lbsnaa123";
    
    // Use FC97 OU which exists in your AD
    $container = "OU=FC97,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in";
    
    $givenname = $test_firstname . " " . $test_lastname;
    $dn = "cn={$givenname},{$container}";
    
    echo "<h3>Configuration:</h3>";
    echo "LDAPServer: {$LDAPServer}<br>";
    echo "Container: {$container}<br>";
    echo "DN to create: {$dn}<br>";
    echo "Username: {$test_username}<br><br>";
    
    // Connect
    $ldapconn = ldap_connect($LDAPServer, $LDAPPORT);
    if (!$ldapconn) {
        echo "<div class='alert alert-danger'>Failed to connect</div>";
        exit;
    }
    
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 20);
    
    // Bind
    $ldapbind = @ldap_bind($ldapconn, $loginDN, $bind_password);
    if (!$ldapbind) {
        echo "<div class='alert alert-danger'>Bind failed: " . ldap_error($ldapconn) . "</div>";
        exit;
    }
    
    echo "<div class='alert alert-success'>✓ Connected and bound successfully</div><br>";
    
    // Check if user exists
    $search_filter = "(&(objectClass=user)(sAMAccountName={$test_username}))";
    $search_result = @ldap_search($ldapconn, $container, $search_filter, ['sAMAccountName']);
    
    if ($search_result) {
        $entries = ldap_get_entries($ldapconn, $search_result);
        if ($entries['count'] > 0) {
            echo "<div class='alert alert-warning'>⚠ User '{$test_username}' already exists in FC97!</div>";
            ldap_close($ldapconn);
            exit;
        }
    }
    
    echo "✓ User does not exist - Ready to create<br><br>";
    
    // Encode password (Java style)
    $newpass = "\"" . $test_password . "\"";
    $newUnicodepass = mb_convert_encoding($newpass, 'UTF-16LE', 'UTF-8');
    
    // Prepare user data (Java style)
    $userdata = [
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user', 'inetOrgPerson'],
        'cn' => $givenname,
        'givenName' => $test_firstname,
        'sn' => $test_lastname,
        'displayName' => $givenname,
        'sAMAccountName' => $test_username,
        'userPrincipalName' => "{$test_username}@lbsnaa.gov.in",
        'userAccountControl' => '544',
        'pwdLastSet' => '-1',
        'unicodePwd' => $newUnicodepass,
        'mail' => $test_email,
        'telephoneNumber' => $test_phone,
        'mobile' => $test_phone,
        'instanceType' => '4',
        'accountExpires' => '0'
    ];
    
    echo "<h3>Attempting to create user in FC97 OU...</h3>";
    $result = @ldap_add($ldapconn, $dn, $userdata);
    
    if ($result) {
        echo "<div class='alert alert-success'>✓ SUCCESS! User created in FC97 OU!</div>";
        echo "DN: {$dn}<br>";
        echo "Username: {$test_username}<br>";
        
        // Enable account
        $modify = [
            'userAccountControl' => '512',
            'pwdLastSet' => '-1'
        ];
        ldap_modify($ldapconn, $dn, $modify);
        echo "✓ Account enabled<br>";
        
    } else {
        $errno = ldap_errno($ldapconn);
        $error = ldap_error($ldapconn);
        echo "<div class='alert alert-danger'>✗ FAILED to create user</div>";
        echo "Error {$errno}: {$error}<br>";
        
        if ($errno == 50) {
            echo "<br><strong>SOLUTION:</strong> The bind user lacks write permissions on FC97 OU.<br>";
            echo "Contact AD administrator to grant 'Create User objects' permission to: <strong>{$loginDN}</strong><br>";
            echo "on OU: <strong>{$container}</strong>";
        } elseif ($errno == 19) {
            echo "<br>Password doesn't meet complexity requirements. Try a stronger password.";
        } else {
            echo "<br>Check if the bind user has permissions to create objects in FC97 OU.";
        }
    }
    
    ldap_close($ldapconn);
}
?>