<?php
// /local/form/test_create_ad_user_fc97_advanced.php

require_once('../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$test_username = optional_param('username', 'testuser11', PARAM_TEXT);
$test_password = optional_param('password', 'Admin@123456', PARAM_TEXT);
$test_firstname = optional_param('firstname', 'Test', PARAM_TEXT);
$test_lastname = optional_param('lastname', 'User11', PARAM_TEXT);
$test_phone = optional_param('phone', '8888777777', PARAM_TEXT);
$test_email = optional_param('email', 'test11@lbsnaa.gov.in', PARAM_TEXT);

echo "<h2>Test Creating AD User in FC97 OU (Advanced)</h2>";

echo "<form method='post'>";
echo "<table border='0'>";
echo "处<td>Username:</td><td><input type='text' name='username' value='{$test_username}' size='30'></td></tr>";
echo "处<td>First Name:</td><td><input type='text' name='firstname' value='{$test_firstname}' size='30'></td></tr>";
echo "处<td>Last Name:</td><td><input type='text' name='lastname' value='{$test_lastname}' size='30'></td></tr>";
echo "处<td>Password:</td><td><input type='password' name='password' value='{$test_password}' size='30'></td></tr>";
echo "处<td>Email:</td><td><input type='text' name='email' value='{$test_email}' size='30'></td></tr>";
echo "处<td>Phone:</td><td><input type='text' name='phone' value='{$test_phone}' size='30'></td></tr>";
echo "处<td></td><td><input type='submit' value='Test Create User in FC97' style='background:#4CAF50;color:white;padding:10px;border:none;'></td></tr>";
echo "</table>";
echo "</form><br><hr><br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $LDAPServer = "103.225.204.25";
    $LDAPPORT = 389;
    $loginDN = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
    $bind_password = "lbsnaa123";
    
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
    
    // COMPLETE user data with ALL required AD attributes
    $userdata = [
        // Required object classes
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
        
        // Required attributes
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
        'accountExpires' => '0',
        
        // Optional but good to have
        'mail' => $test_email,
        'telephoneNumber' => $test_phone,
        'mobile' => $test_phone,
        
        // Add objectCategory (often required)
        'objectCategory' => "CN=Person,CN=Schema,CN=Configuration,DC=lbsnaa,DC=gov,DC=in"
    ];
    
    echo "<h3>Attempting to create user in FC97 OU...</h3>";
    echo "With password: {$test_password}<br>";
    echo "Password encoded length: " . strlen($newUnicodepass) . "<br><br>";
    
    // Try to create user
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
        echo "Error {$errno}: {$error}<br><br>";
        
        // Get extended error information if available
        if (function_exists('ldap_get_option')) {
            $extended_error = null;
            ldap_get_option($ldapconn, LDAP_OPT_ERROR_STRING, $extended_error);
            if ($extended_error) {
                echo "<strong>Extended Error:</strong> " . htmlspecialchars($extended_error) . "<br>";
            }
        }
        
        // Troubleshooting suggestions
        echo "<br><strong>Troubleshooting:</strong><br>";
        echo "<ul>";
        echo "<li>Try a stronger password: <strong>Admin@12345678</strong> or <strong>Test@2024!Pass</strong></li>";
        echo "<li>Check if the OU 'FC97' allows user creation (may be restricted)</li>";
        echo "<li>The bind user may need additional permissions like 'Write all properties'</li>";
        echo "<li>Check if there are any password policy restrictions in this OU</li>";
        echo "</ul>";
        
        // Try alternative approach - create with minimal attributes first
        echo "<br><strong>Trying alternative approach with minimal attributes...</strong><br>";
        
        $minimal_userdata = [
            'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
            'cn' => $givenname,
            'sn' => $test_lastname,
            'sAMAccountName' => $test_username,
            'userPrincipalName' => "{$test_username}@lbsnaa.gov.in",
            'userAccountControl' => '544',
            'unicodePwd' => $newUnicodepass,
            'instanceType' => '4'
        ];
        
        $result2 = @ldap_add($ldapconn, $dn, $minimal_userdata);
        
        if ($result2) {
            echo "<div class='alert alert-success'>✓ SUCCESS with minimal attributes!</div>";
            echo "DN: {$dn}<br>";
            
            // Add remaining attributes
            $add_attrs = [
                'givenName' => $test_firstname,
                'displayName' => $givenname,
                'mail' => $test_email,
                'telephoneNumber' => $test_phone,
                'mobile' => $test_phone,
                'pwdLastSet' => '-1'
            ];
            ldap_mod_add($ldapconn, $dn, $add_attrs);
            ldap_modify($ldapconn, $dn, ['userAccountControl' => '512']);
            echo "✓ Additional attributes added<br>";
            
        } else {
            $errno2 = ldap_errno($ldapconn);
            $error2 = ldap_error($ldapconn);
            echo "<div class='alert alert-danger'>✗ Minimal attributes also failed: Error {$errno2}: {$error2}</div>";
        }
    }
    
    ldap_close($ldapconn);
}
?>