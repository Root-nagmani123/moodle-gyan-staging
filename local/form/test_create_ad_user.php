<?php
// /local/form/test_create_ad_user.php

require_once('../../config.php');

// Check if user is admin
require_login();
require_capability('moodle/site:config', context_system::instance());

// Get the username to test
$test_username = optional_param('username', 'testuser8', PARAM_TEXT);
$test_password = optional_param('password', 'Admin@123', PARAM_TEXT);

echo "<h2>Test Creating AD User</h2>";

echo "<form method='post'>";
echo "Username: <input type='text' name='username' value='{$test_username}'><br>";
echo "Password: <input type='password' name='password' value='{$test_password}'><br>";
echo "<input type='submit' value='Test Create User'>";
echo "</form><br><hr><br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $ldap_server = "103.225.204.25";
    $ldap_port = 389;
    $bind_dn = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
    $bind_password = "lbsnaa123";
    $container = "cn=Users,dc=lbsnaa,dc=gov,dc=in";
    $dn = "cn={$test_username},{$container}";
    
    echo "<h3>Testing with:</h3>";
    echo "Bind DN: {$bind_dn}<br>";
    echo "Container: {$container}<br>";
    echo "DN to create: {$dn}<br>";
    echo "Username: {$test_username}<br>";
    echo "Password: ******<br><br>";
    
    // Connect to LDAP
    $ldapconn = ldap_connect($ldap_server, $ldap_port);
    if (!$ldapconn) {
        echo "<div class='alert alert-danger'>Failed to connect to LDAP server</div>";
        exit;
    }
    
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    
    // Bind to LDAP
    $ldapbind = @ldap_bind($ldapconn, $bind_dn, $bind_password);
    if (!$ldapbind) {
        echo "<div class='alert alert-danger'>Bind failed: " . ldap_error($ldapconn) . "</div>";
        ldap_close($ldapconn);
        exit;
    }
    
    echo "<div class='alert alert-success'>✓ Connected and bound successfully</div><br>";
    
    // Check if user already exists
    $search_filter = "(&(objectClass=user)(sAMAccountName={$test_username}))";
    $search_result = @ldap_search($ldapconn, $container, $search_filter, ['sAMAccountName']);
    
    if ($search_result) {
        $entries = ldap_get_entries($ldapconn, $search_result);
        if ($entries['count'] > 0) {
            echo "<div class='alert alert-warning'>⚠ User '{$test_username}' already exists in AD!</div>";
            ldap_close($ldapconn);
            exit;
        } else {
            echo "✓ User does NOT exist in AD - Ready to create<br><br>";
        }
    }
    
    // Encode password for AD (UTF-16LE with quotes)
    $encoded_password = "";
    $password_with_quotes = "\"" . $test_password . "\"";
    for ($i = 0; $i < strlen($password_with_quotes); $i++) {
        $encoded_password .= "{$password_with_quotes[$i]}\000";
    }
    
    // Prepare user data
    $userdata = [
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user', 'inetOrgPerson'],
        'cn' => $test_username,
        'givenName' => 'Test',
        'sn' => 'User',
        'displayName' => "Test User",
        'sAMAccountName' => $test_username,
        'userPrincipalName' => "{$test_username}@lbsnaa.gov.in",
        'userAccountControl' => '544',
        'pwdLastSet' => '-1',
        'unicodePwd' => $encoded_password,
        'mail' => "{$test_username}@test.com",
        'telephoneNumber' => '9999999999',
        'instanceType' => '4',
        'accountExpires' => '0'
    ];
    
    echo "<h3>Attempting to create user...</h3>";
    $result = @ldap_add($ldapconn, $dn, $userdata);
    
    if ($result) {
        echo "<div class='alert alert-success'>✓ SUCCESS! User '{$test_username}' created in AD!</div>";
        
        // Enable account
        $modify = [
            'userAccountControl' => '512',
            'pwdLastSet' => '-1'
        ];
        ldap_modify($ldapconn, $dn, $modify);
        
        echo "<br>Verifying user was created...<br>";
        $verify = @ldap_search($ldapconn, $container, $search_filter, ['sAMAccountName', 'cn']);
        if ($verify) {
            $entries = ldap_get_entries($ldapconn, $verify);
            if ($entries['count'] > 0) {
                echo "<div class='alert alert-success'>✓ Verified: User exists in AD!</div>";
            }
        }
        
        // Option to delete
        echo "<br><form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='username' value='{$test_username}'>";
        echo "<input type='hidden' name='password' value='{$test_password}'>";
        echo "<input type='submit' name='delete_user' value='Delete Test User' style='background-color:#dc3545;color:white;padding:5px 10px;border:none;border-radius:3px;'>";
        echo "</form>";
        
    } else {
        $error = ldap_error($ldapconn);
        $errno = ldap_errno($ldapconn);
        echo "<div class='alert alert-danger'>✗ FAILED to create user</div>";
        echo "Error {$errno}: {$error}<br>";
        
        if ($errno == 50) {
            echo "<br><strong>SOLUTION:</strong> The bind user lacks write permissions.<br>";
            echo "Contact AD administrator to grant 'Create User objects' permission to: <strong>{$bind_dn}</strong><br>";
            echo "on container: <strong>{$container}</strong>";
        } elseif ($errno == 19) {
            echo "<br>Password doesn't meet complexity requirements. Try a stronger password.";
        } elseif ($errno == 68) {
            echo "<br>User already exists.";
        }
    }
    
    ldap_close($ldapconn);
}

// Handle delete
if (isset($_POST['delete_user']) && !empty($_POST['username'])) {
    $username_to_delete = $_POST['username'];
    $container = "cn=Users,dc=lbsnaa,dc=gov,dc=in";
    $dn = "cn={$username_to_delete},{$container}";
    
    $ldapconn = ldap_connect("103.225.204.25", 389);
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    $ldapbind = @ldap_bind($ldapconn, "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in", "lbsnaa123");
    
    if ($ldapbind) {
        if (ldap_delete($ldapconn, $dn)) {
            echo "<div class='alert alert-info'>User '{$username_to_delete}' deleted from AD.</div>";
        } else {
            echo "<div class='alert alert-warning'>Failed to delete user: " . ldap_error($ldapconn) . "</div>";
        }
    }
    ldap_close($ldapconn);
}
?>