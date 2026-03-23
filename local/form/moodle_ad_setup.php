<?php
// /local/form/moodle_ad_setup.php

require_once('../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$action = optional_param('action', '', PARAM_TEXT);
$test_username = optional_param('username', 'moodleuser1', PARAM_TEXT);
$test_password = optional_param('password', 'Admin@12345678', PARAM_TEXT);
$test_firstname = optional_param('firstname', 'Moodle', PARAM_TEXT);
$test_lastname = optional_param('lastname', 'User', PARAM_TEXT);
$test_phone = optional_param('phone', '9999999999', PARAM_TEXT);
$test_email = optional_param('email', 'moodleuser@lbsnaa.gov.in', PARAM_TEXT);

$LDAPServer = "103.225.204.25";
$loginDN = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
$bind_password = "lbsnaa123";
$new_ou_name = "MoodleUsers";
$parent_ou = "OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in";
$target_ou = "OU={$new_ou_name},{$parent_ou}";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Moodle AD Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; }
        .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        button, .button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
        button:hover { background: #45a049; }
        .button-danger { background: #dc3545; }
        .button-danger:hover { background: #c82333; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        input[type=\"text\"], input[type=\"password\"] { padding: 8px; width: 250px; border: 1px solid #ddd; border-radius: 4px; }
        .step { background: #e9ecef; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #4CAF50; }
        .step-number { background: #4CAF50; color: white; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Moodle Active Directory Setup</h1>
    <p>This tool will help you set up a dedicated OU for Moodle user creation in Active Directory.</p>
    
    <div class='step'>
        <h3><span class='step-number'>1</span> Create New OU: MoodleUsers</h3>
        <form method='post'>
            <input type='hidden' name='action' value='create_ou'>
            <button type='submit'>Create OU: {$new_ou_name}</button>
        </form>
    </div>";

// Handle OU Creation
if ($action === 'create_ou') {
    echo "<h3>📁 Creating Organizational Unit</h3>";
    
    $ldapconn = ldap_connect($LDAPServer, 389);
    if (!$ldapconn) {
        echo "<div class='alert alert-danger'>Failed to connect to LDAP server</div>";
    } else {
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
        
        $ldapbind = @ldap_bind($ldapconn, $loginDN, $bind_password);
        if (!$ldapbind) {
            echo "<div class='alert alert-danger'>Bind failed: " . ldap_error($ldapconn) . "</div>";
        } else {
            echo "<div class='alert alert-success'>✓ Connected and bound successfully</div>";
            
            // Check if OU exists
            $check = @ldap_read($ldapconn, $target_ou, "(objectClass=*)", ['ou']);
            if ($check) {
                echo "<div class='alert alert-warning'>⚠ OU '{$new_ou_name}' already exists!</div>";
                $entries = ldap_get_entries($ldapconn, $check);
                echo "Location: " . $entries[0]['dn'] . "<br>";
            } else {
                // Create new OU
                $ou_data = [
                    'objectClass' => ['top', 'organizationalUnit'],
                    'ou' => $new_ou_name,
                    'description' => 'Moodle User Registration OU - Created by Moodle Plugin'
                ];
                
                $result = @ldap_add($ldapconn, $target_ou, $ou_data);
                
                if ($result) {
                    echo "<div class='alert alert-success'>✅ SUCCESS! New OU created: {$target_ou}</div>";
                } else {
                    $errno = ldap_errno($ldapconn);
                    $error = ldap_error($ldapconn);
                    echo "<div class='alert alert-danger'>✗ Failed to create OU: Error {$errno}: {$error}</div>";
                    
                    if ($errno == 50) {
                        echo "<div class='alert alert-warning'>The bind user needs 'Create Organizational Unit' permission on parent OU: {$parent_ou}</div>";
                    }
                }
            }
        }
        ldap_close($ldapconn);
    }
}

echo "<div class='step'>
        <h3><span class='step-number'>2</span> Set Permissions on MoodleUsers OU</h3>
        <form method='post'>
            <input type='hidden' name='action' value='show_permissions'>
            <button type='submit'>Show Permission Instructions</button>
        </form>
      </div>";

// Show Permissions Instructions
if ($action === 'show_permissions') {
    echo "<div class='alert alert-info'>
            <h4>📋 Permission Requirements</h4>
            <p>The bind user <strong>{$loginDN}</strong> needs these permissions on <strong>{$target_ou}</strong>:</p>
            <ul>
                <li>✅ Create User objects</li>
                <li>✅ Write all properties</li>
                <li>✅ Read all properties</li>
            </ul>
          </div>";
    
    echo "<div class='alert alert-info'>
            <h4>🔧 Instructions for AD Administrator</h4>
            <ol>
                <li>Open <strong>Active Directory Users and Computers</strong></li>
                <li>Navigate to: <code>{$target_ou}</code></li>
                <li>Right-click → <strong>Properties</strong> → <strong>Security</strong> tab</li>
                <li>Click <strong>Advanced</strong> → <strong>Add</strong></li>
                <li>Select Principal: <code>{$loginDN}</code></li>
                <li>Set <strong>Type</strong>: Allow</li>
                <li>Set <strong>Applies to</strong>: This object and all descendant objects</li>
                <li>Permissions: <strong>Create User objects</strong>, <strong>Write all properties</strong>, <strong>Read all properties</strong></li>
                <li>Click <strong>OK</strong> to apply</li>
            </ol>
          </div>";
}

echo "<div class='step'>
        <h3><span class='step-number'>3</span> Test User Creation</h3>
        <form method='post'>
            <input type='hidden' name='action' value='test_create'>
            <table>
                <tr><th>Field</th><th>Value</th></tr>
                <tr><td>Username:</td><td><input type='text' name='username' value='{$test_username}'></td></tr>
                <tr><td>First Name:</td><td><input type='text' name='firstname' value='{$test_firstname}'></td></tr>
                <tr><td>Last Name:</td><td><input type='text' name='lastname' value='{$test_lastname}'></td></tr>
                <tr><td>Password:</td><td><input type='password' name='password' value='{$test_password}'></td></tr>
                <tr><td>Email:</td><td><input type='text' name='email' value='{$test_email}'></td></tr>
                <tr><td>Phone:</td><td><input type='text' name='phone' value='{$test_phone}'></td></tr>
            </table>
            <button type='submit'>Test Create User in MoodleUsers OU</button>
        </form>
      </div>";

// Test User Creation
if ($action === 'test_create') {
    echo "<h3>📝 Testing User Creation</h3>";
    
    $ldapconn = ldap_connect($LDAPServer, 389);
    if (!$ldapconn) {
        echo "<div class='alert alert-danger'>Failed to connect to LDAP server</div>";
    } else {
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
        
        $ldapbind = @ldap_bind($ldapconn, $loginDN, $bind_password);
        if (!$ldapbind) {
            echo "<div class='alert alert-danger'>Bind failed: " . ldap_error($ldapconn) . "</div>";
        } else {
            echo "<div class='alert alert-success'>✓ Connected and bound successfully</div>";
            
            // Check if OU exists
            $check_ou = @ldap_read($ldapconn, $target_ou, "(objectClass=*)", ['ou']);
            if (!$check_ou) {
                echo "<div class='alert alert-danger'>✗ OU does not exist! Please create it first (Step 1).</div>";
                ldap_close($ldapconn);
                exit;
            }
            echo "✓ OU exists: {$target_ou}<br>";
            
            $givenname = $test_firstname . " " . $test_lastname;
            $dn = "cn={$givenname},{$target_ou}";
            
            // Check if user exists
            $search_filter = "(&(objectClass=user)(sAMAccountName={$test_username}))";
            $search_result = @ldap_search($ldapconn, $target_ou, $search_filter, ['sAMAccountName']);
            
            if ($search_result) {
                $entries = ldap_get_entries($ldapconn, $search_result);
                if ($entries['count'] > 0) {
                    echo "<div class='alert alert-warning'>⚠ User '{$test_username}' already exists!</div>";
                    ldap_close($ldapconn);
                    exit;
                }
            }
            
            echo "✓ User does not exist - Ready to create<br>";
            
            // Encode password (Java style)
            $newpass = "\"" . $test_password . "\"";
            $newUnicodepass = mb_convert_encoding($newpass, 'UTF-16LE', 'UTF-8');
            
            // Prepare user data
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
                'mail' => $test_email,
                'telephoneNumber' => $test_phone,
                'mobile' => $test_phone,
                'instanceType' => '4',
                'accountExpires' => '0'
            ];
            
            echo "<h4>Attempting to create user...</h4>";
            $result = @ldap_add($ldapconn, $dn, $userdata);
            
            if ($result) {
                echo "<div class='alert alert-success'>✅ SUCCESS! User created in MoodleUsers OU!</div>";
                echo "DN: {$dn}<br>";
                echo "Username: {$test_username}<br>";
                
                // Enable account
                $modify = [
                    'userAccountControl' => '512',
                    'pwdLastSet' => '-1'
                ];
                ldap_modify($ldapconn, $dn, $modify);
                echo "✓ Account enabled<br>";
                echo "<div class='alert alert-success'>🎉 The new OU is working! User was successfully created in AD.</div>";
                
                // Option to delete test user
                echo "<form method='post' style='margin-top:15px;'>
                        <input type='hidden' name='action' value='delete_user'>
                        <input type='hidden' name='username' value='{$test_username}'>
                        <input type='hidden' name='firstname' value='{$test_firstname}'>
                        <input type='hidden' name='lastname' value='{$test_lastname}'>
                        <button type='submit' class='button-danger' onclick='return confirm(\"Delete this test user from AD?\")'>🗑️ Delete Test User</button>
                      </form>";
            } else {
                $errno = ldap_errno($ldapconn);
                $error = ldap_error($ldapconn);
                echo "<div class='alert alert-danger'>✗ FAILED to create user</div>";
                echo "Error {$errno}: {$error}<br>";
                
                if ($errno == 50) {
                    echo "<div class='alert alert-warning'>
                            <strong>The bind user needs these permissions on the new OU:</strong>
                            <ul>
                                <li>Create User objects</li>
                                <li>Write all properties</li>
                                <li>Read all properties</li>
                            </ul>
                            Please ask AD administrator to grant these permissions on: {$target_ou}
                          </div>";
                } elseif ($errno == 53) {
                    echo "<div class='alert alert-warning'>
                            <strong>Error 53: Server unwilling to perform</strong><br>
                            Check password complexity requirements. Try: Admin@12345678 or Test@2024!Pass
                          </div>";
                }
            }
        }
        ldap_close($ldapconn);
    }
}

// Delete test user
if ($action === 'delete_user') {
    $delete_username = optional_param('username', '', PARAM_TEXT);
    $delete_firstname = optional_param('firstname', '', PARAM_TEXT);
    $delete_lastname = optional_param('lastname', '', PARAM_TEXT);
    
    if ($delete_username) {
        $givenname = $delete_firstname . " " . $delete_lastname;
        $dn = "cn={$givenname},{$target_ou}";
        
        $ldapconn = ldap_connect($LDAPServer, 389);
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
        $ldapbind = @ldap_bind($ldapconn, $loginDN, $bind_password);
        
        if ($ldapbind) {
            if (@ldap_delete($ldapconn, $dn)) {
                echo "<div class='alert alert-success'>✓ User '{$delete_username}' deleted from AD.</div>";
            } else {
                echo "<div class='alert alert-warning'>Failed to delete user: " . ldap_error($ldapconn) . "</div>";
            }
        }
        ldap_close($ldapconn);
    }
}

echo "<hr>
      <p><strong>Summary:</strong></p>
      <ul>
          <li>OU Path: <code>{$target_ou}</code></li>
          <li>Bind User: <code>{$loginDN}</code></li>
          <li>Once permissions are granted, users will be created in this OU</li>
      </ul>
      <p><a href='?'>← Back to Start</a></p>
</div>
</body>
</html>";
?>