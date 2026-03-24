<?php
// /local/form/test_java_server_creation.php

require_once('../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Java Server User Creation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 1200px; margin: 0 auto; background: #252526; padding: 20px; border-radius: 8px; }
        .success { color: #4ec9b0; font-weight: bold; }
        .error { color: #f48771; font-weight: bold; }
        .info { color: #9cdcfe; }
        .warning { color: #ce9178; }
        pre { background: #2d2d2d; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { background: #0e639c; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        input[type=\"text\"], input[type=\"password\"] { background: #3c3c3c; color: #fff; border: 1px solid #555; padding: 8px; border-radius: 4px; margin: 5px; width: 300px; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #2d2d2d; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Test Java Server User Creation</h1>
    <p>Testing with Java credentials and server: <strong>192.168.1.101</strong></p>";

// Java server configuration
$java_server = "192.168.1.101";
$java_port = 389;
$java_loginDN = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
$java_password = "lbsnaa123";
$java_container = "ou=FC97,ou=LBSNAA,dc=lbsnaa,dc=gov,dc=in";

// Get parameters from form
$action = optional_param('action', '', PARAM_TEXT);
$test_username = optional_param('username', 'testuser_' . time(), PARAM_TEXT);
$test_password = optional_param('password', 'Admin@12345678', PARAM_TEXT);
$test_firstname = optional_param('firstname', 'Test', PARAM_TEXT);
$test_lastname = optional_param('lastname', 'User', PARAM_TEXT);
$test_phone = optional_param('phone', '9999999999', PARAM_TEXT);
$test_email = optional_param('email', '', PARAM_EMAIL);

// Step 1: Test connectivity to Java server
echo "<h3>Step 1: Testing Connectivity to Java Server</h3>";

$test_conn = @fsockopen($java_server, $java_port, $errno, $errstr, 5);
if ($test_conn) {
    echo "<div class='success'>✓ Java server {$java_server} is reachable on port {$java_port}</div>";
    fclose($test_conn);
    $server_reachable = true;
} else {
    echo "<div class='error'>✗ Cannot reach Java server {$java_server}: {$errstr}</div>";
    echo "<div class='warning'>The Java server may not be accessible from this network.</div>";
    $server_reachable = false;
}

if ($server_reachable) {
    // Step 2: Test LDAP connection and bind
    echo "<h3>Step 2: Testing LDAP Connection</h3>";
    
    $ldapconn = @ldap_connect($java_server, $java_port);
    if ($ldapconn) {
        echo "<div class='success'>✓ LDAP connection successful</div>";
        
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 30);
        
        $bind = @ldap_bind($ldapconn, $java_loginDN, $java_password);
        if ($bind) {
            echo "<div class='success'>✓ Successfully bound with Java credentials!</div>";
            echo "<div>Bind DN: {$java_loginDN}</div>";
            $bound = true;
        } else {
            echo "<div class='error'>✗ Bind failed: " . ldap_error($ldapconn) . "</div>";
            $bound = false;
        }
        
        if ($bound) {
            // Step 3: Check if Java container exists
            echo "<h3>Step 3: Checking Java Container</h3>";
            $check = @ldap_read($ldapconn, $java_container, "(objectClass=*)", ['ou']);
            if ($check) {
                echo "<div class='success'>✓ Java container exists: {$java_container}</div>";
                
                // Get sample users from container
                $search = @ldap_search($ldapconn, $java_container, "(objectClass=user)", ['cn', 'sAMAccountName'], 0, 5);
                if ($search) {
                    $entries = ldap_get_entries($ldapconn, $search);
                    if ($entries['count'] > 0) {
                        echo "<div class='info'>Sample users in this container:</div>";
                        echo "<ul>";
                        for ($i = 0; $i < $entries['count']; $i++) {
                            $cn = isset($entries[$i]['cn'][0]) ? $entries[$i]['cn'][0] : 'N/A';
                            echo "<li>{$cn}</li>";
                        }
                        echo "</ul>";
                    }
                }
            } else {
                echo "<div class='error'>✗ Java container does not exist: {$java_container}</div>";
                echo "<div class='info'>Trying to create container... but may need permissions.</div>";
            }
            
            // Step 4: Test User Creation Form
            echo "<hr>";
            echo "<h3>Step 4: Test User Creation</h3>";
            echo "<form method='post'>
                    <input type='hidden' name='action' value='create_user'>
                    <table>
                        <tr><th>Field</th><th>Value</th></tr>
                        <tr><td>Username (sAMAccountName):</td><td><input type='text' name='username' value='{$test_username}' required></td></tr>
                        <tr><td>First Name (givenName):</td><td><input type='text' name='firstname' value='{$test_firstname}' required></td></tr>
                        <tr><td>Last Name (sn):</td><td><input type='text' name='lastname' value='{$test_lastname}' required></td></tr>
                        <tr><td>Password:</td><td><input type='password' name='password' value='{$test_password}' required></td></tr>
                        <tr><td>Phone:</td><td><input type='text' name='phone' value='{$test_phone}'></td></tr>
                        <tr><td>Email:</td><td><input type='text' name='email' value='{$test_email}' placeholder='optional'></td></tr>
                        <tr><td colspan='2'><button type='submit'>Create User on Java Server</button></td></tr>
                    </table>
                  </form>";
            
            if ($action === 'create_user') {
                echo "<h3>Creating User on Java Server</h3>";
                
                $givenname = $test_firstname . " " . $test_lastname;
                $dn = "cn={$givenname},{$java_container}";
                
                echo "DN: {$dn}<br>";
                echo "Container: {$java_container}<br>";
                echo "Server: {$java_server}<br><br>";
                
                // Encode password (Java style)
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
                
                // Add optional fields
                if (!empty($test_email)) {
                    $userdata['mail'] = $test_email;
                }
                if (!empty($test_phone)) {
                    $userdata['telephoneNumber'] = $test_phone;
                    $userdata['mobile'] = $test_phone;
                }
                
                $result = @ldap_add($ldapconn, $dn, $userdata);
                
                if ($result) {
                    echo "<div class='success'>✅ SUCCESS! User created on Java server!</div>";
                    echo "User: {$test_username}<br>";
                    echo "DN: {$dn}<br>";
                    
                    // Enable account
                    ldap_modify($ldapconn, $dn, ['userAccountControl' => '512']);
                    echo "✓ Account enabled<br>";
                    
                    // Verify
                    $verify = @ldap_search($ldapconn, $java_container, "(sAMAccountName={$test_username})");
                    if ($verify) {
                        $entries = ldap_get_entries($ldapconn, $verify);
                        if ($entries['count'] > 0) {
                            echo "<div class='success'>✓ Verified: User exists on Java server!</div>";
                        }
                    }
                    
                    // Delete button
                    echo "<form method='post' style='margin-top:15px;'>
                            <input type='hidden' name='action' value='delete_user'>
                            <input type='hidden' name='username' value='{$test_username}'>
                            <input type='hidden' name='dn' value='{$dn}'>
                            <button type='submit' style='background:#dc3545;'>🗑️ Delete Test User</button>
                          </form>";
                    
                    echo "<div class='success'>🎉 Java server is WORKING! Use this server in your code.</div>";
                    
                    // Working code
                    echo "<hr>";
                    echo "<h2>📝 Working Registration Code</h2>";
                    echo "<pre>
function local_form_create_ad_user(\$username, \$firstname, \$lastname, \$password, \$email, \$phone) {
    
    // Use Java server (has write permissions)
    \$LDAPServer = \"192.168.1.101\";
    \$loginDN = \"cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in\";
    \$bind_password = \"lbsnaa123\";
    
    // Java container
    \$container = \"ou=FC97,ou=LBSNAA,dc=lbsnaa,dc=gov,dc=in\";
    
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
                    echo "<div class='error'>✗ Failed to create user: Error {$errno}: {$error}</div>";
                    
                    if ($errno == 50) {
                        echo "<div class='info'>Bind user lacks write permissions on this server.</div>";
                    } elseif ($errno == 53) {
                        echo "<div class='info'>Server unwilling - container may not exist or policy restriction.</div>";
                    }
                }
            }
            
            // Delete user
            if ($action === 'delete_user') {
                $delete_dn = optional_param('dn', '', PARAM_TEXT);
                if ($delete_dn) {
                    if (@ldap_delete($ldapconn, $delete_dn)) {
                        echo "<div class='success'>✓ User deleted from Java server.</div>";
                    } else {
                        echo "<div class='error'>Failed to delete user: " . ldap_error($ldapconn) . "</div>";
                    }
                }
            }
        }
        ldap_close($ldapconn);
    } else {
        echo "<div class='error'>✗ Cannot create LDAP connection to {$java_server}</div>";
    }
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<ul>";
echo "<li>Java Server: <strong>192.168.1.101</strong></li>";
echo "<li>Java Container: <strong>{$java_container}</strong></li>";
echo "<li>Java Credentials: <strong>{$java_loginDN}</strong></li>";
echo "</ul>";

if (!$server_reachable) {
    echo "<div class='warning'>⚠ The Java server is not reachable from this PHP server.</div>";
    echo "<div class='info'>Solutions:
    <ul>
        <li>Contact network administrator to allow access to 192.168.1.101</li>
        <li>OR ask AD admin to enable write permissions on 103.225.204.25</li>
        <li>OR find another domain controller that allows writes</li>
    </ul>
    </div>";
}

echo "</div></body></html>";
?>