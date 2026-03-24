<?php
// /local/form/create_and_test_ou.php

require_once('../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$action = optional_param('action', '', PARAM_TEXT);
$test_username = optional_param('username', 'testuser_' . time(), PARAM_TEXT);
$test_password = optional_param('password', 'Admin@12345678', PARAM_TEXT);
$test_firstname = optional_param('firstname', 'Test', PARAM_TEXT);
$test_lastname = optional_param('lastname', 'User', PARAM_TEXT);

$LDAPServer = "103.225.204.25";
$loginDN = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
$bind_password = "lbsnaa123";
$parent_ou = "OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in";
$new_ou_name = "MoodleUsers";
$target_ou = "OU={$new_ou_name},{$parent_ou}";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Create MoodleUsers OU and Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #17a2b8; }
        button, .button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        input[type=\"text\"], input[type=\"password\"] { padding: 8px; width: 250px; border: 1px solid #ddd; border-radius: 4px; margin: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Create MoodleUsers OU and Test User Creation</h1>";

// Connect to LDAP
$ldapconn = ldap_connect($LDAPServer, 389);
if (!$ldapconn) {
    die("<div class='error'>Failed to connect to LDAP server</div></div></body></html>");
}

ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 30);

$ldapbind = @ldap_bind($ldapconn, $loginDN, $bind_password);
if (!$ldapbind) {
    die("<div class='error'>Bind failed: " . ldap_error($ldapconn) . "</div></div></body></html>");
}

echo "<div class='success'>✓ Connected and bound successfully to {$LDAPServer}</div>";

// ==================== STEP 1: CREATE OU ====================
echo "<h2>Step 1: Create OU: {$new_ou_name}</h2>";

// Check if OU exists
$check = @ldap_read($ldapconn, $target_ou, "(objectClass=*)", ['ou']);
if ($check) {
    echo "<div class='info'>✓ OU already exists: {$target_ou}</div>";
    $entries = ldap_get_entries($ldapconn, $check);
    echo "OU Name: " . $entries[0]['ou'][0] . "<br>";
} else {
    echo "Creating new OU: {$new_ou_name}<br>";
    
    $ou_data = [
        'objectClass' => ['top', 'organizationalUnit'],
        'ou' => $new_ou_name,
        'description' => 'Moodle User Registration OU'
    ];
    
    $result = @ldap_add($ldapconn, $target_ou, $ou_data);
    
    if ($result) {
        echo "<div class='success'>✅ SUCCESS! OU created: {$target_ou}</div>";
    } else {
        $errno = ldap_errno($ldapconn);
        $error = ldap_error($ldapconn);
        echo "<div class='error'>✗ Failed to create OU: Error {$errno}: {$error}</div>";
        
        if ($errno == 50) {
            echo "<div class='info'>Bind user needs 'Create Organizational Unit' permission on parent OU: {$parent_ou}</div>";
        }
        ldap_close($ldapconn);
        exit;
    }
}

// ==================== STEP 2: TEST USER CREATION ====================
echo "<h2>Step 2: Test User Creation in {$new_ou_name}</h2>";

// Test form
echo "<form method='post'>
      <input type='hidden' name='action' value='test_create'>
      <table>
      <tr><th>Field</th><th>Value</th></tr>
      <tr><td>Username:</td><td><input type='text' name='username' value='{$test_username}'></td></tr>
      <tr><td>First Name:</td><td><input type='text' name='firstname' value='{$test_firstname}'></td></tr>
      <tr><td>Last Name:</td><td><input type='text' name='lastname' value='{$test_lastname}'></td></tr>
      <tr><td>Password:</td><td><input type='password' name='password' value='{$test_password}'></td></tr>
      <tr><td colspan='2'><button type='submit'>Test Create User</button></td></tr>
      </table>
      </form>";

if ($action === 'test_create') {
    echo "<h3>Creating user: {$test_username}</h3>";
    
    $cn = $test_username; // Use username as CN (no spaces)
    $dn = "cn={$cn},{$target_ou}";
    
    echo "DN: {$dn}<br>";
    echo "OU: {$target_ou}<br>";
    
    // Encode password
    $newpass = "\"" . $test_password . "\"";
    $newUnicodepass = mb_convert_encoding($newpass, 'UTF-16LE', 'UTF-8');
    
    // Complete user data
    $userdata = [
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
        'cn' => $cn,
        'sn' => $test_lastname,
        'givenName' => $test_firstname,
        'displayName' => "{$test_firstname} {$test_lastname}",
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
        echo "<div class='success'>✅ SUCCESS! User created in {$new_ou_name}!</div>";
        echo "DN: {$dn}<br>";
        echo "Username: {$test_username}<br>";
        
        // Enable account
        ldap_modify($ldapconn, $dn, ['userAccountControl' => '512']);
        echo "✓ Account enabled<br>";
        
        // Verify
        $verify = ldap_search($ldapconn, $target_ou, "(sAMAccountName={$test_username})");
        if ($verify) {
            $entries = ldap_get_entries($ldapconn, $verify);
            if ($entries['count'] > 0) {
                echo "<div class='success'>✓ Verified: User exists in AD!</div>";
            }
        }
        
        echo "<div class='success'>🎉 The MoodleUsers OU is working! Use this OU in your registration code.</div>";
        
        // Delete button
        echo "<form method='post' style='margin-top:15px;'>
                <input type='hidden' name='action' value='delete_user'>
                <input type='hidden' name='username' value='{$test_username}'>
                <input type='hidden' name='firstname' value='{$test_firstname}'>
                <input type='hidden' name='lastname' value='{$test_lastname}'>
                <button type='submit' style='background:#dc3545;'>🗑️ Delete Test User</button>
              </form>";
        
    } else {
        $errno = ldap_errno($ldapconn);
        $error = ldap_error($ldapconn);
        echo "<div class='error'>✗ FAILED to create user: Error {$errno}: {$error}</div>";
        
        // Get extended error
        ldap_get_option($ldapconn, LDAP_OPT_ERROR_STRING, $ext_error);
        if ($ext_error) {
            echo "<pre>Extended: " . htmlspecialchars($ext_error) . "</pre>";
        }
        
        echo "<div class='info'>Troubleshooting:</div>";
        echo "<ul>";
        echo "<li>Check if bind user has 'Create User objects' permission on {$target_ou}</li>";
        echo "<li>Try a different password: Admin@12345678</li>";
        echo "<li>Check if the OU was created successfully</li>";
        echo "</ul>";
    }
}

// ==================== DELETE TEST USER ====================
if ($action === 'delete_user') {
    $delete_username = optional_param('username', '', PARAM_TEXT);
    $delete_firstname = optional_param('firstname', '', PARAM_TEXT);
    $delete_lastname = optional_param('lastname', '', PARAM_TEXT);
    
    if ($delete_username) {
        $cn = $delete_username;
        $dn = "cn={$cn},{$target_ou}";
        
        if (@ldap_delete($ldapconn, $dn)) {
            echo "<div class='success'>✓ User '{$delete_username}' deleted from AD.</div>";
        } else {
            echo "<div class='error'>Failed to delete user: " . ldap_error($ldapconn) . "</div>";
        }
    }
}

// ==================== UPDATE YOUR REGISTRATION CODE ====================
echo "<h2>📝 Update Your Registration Code</h2>";
echo "<div class='info'>Use this OU path in your registration function:</div>";
echo "<pre style='background:#f8f9fa; padding:10px; border-radius:4px;'>
function local_form_create_ad_user(\$username, \$firstname, \$lastname, \$password, \$email, \$phone) {
    \$container = \"OU=MoodleUsers,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in\";
    \$dn = \"cn={\$username},{\$container}\";
    // ... rest of your code
}
</pre>";

ldap_close($ldapconn);

echo "<hr>
      <p><a href='?'>← Run Again</a></p>
</div>
</body>
</html>";
?>