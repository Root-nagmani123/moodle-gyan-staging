<?php
// /local/form/ad_comprehensive_diagnostic.php

require_once('../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$LDAPServer = "103.225.204.25";
$loginDN = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
$bind_password = "lbsnaa123";
$target_ou = "OU=MoodleUsers,OU=LBSNAA,DC=lbsnaa,DC=gov,DC=in";

echo "<!DOCTYPE html>
<html>
<head>
    <title>AD Comprehensive Diagnostic</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 1400px; margin: 0 auto; }
        .success { color: #4ec9b0; font-weight: bold; }
        .error { color: #f48771; font-weight: bold; }
        .warning { color: #ce9178; }
        .info { color: #9cdcfe; }
        pre { background: #252526; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .section { margin: 20px 0; padding: 15px; border-left: 3px solid #007acc; background: #252526; border-radius: 5px; }
        h1, h2, h3 { color: #fff; margin-top: 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #2d2d2d; color: #fff; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .test-pass { background: #1e3a2f; border-left: 4px solid #4ec9b0; }
        .test-fail { background: #3a1e1e; border-left: 4px solid #f48771; }
        hr { border-color: #444; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 AD Comprehensive Diagnostic Tool</h1>
    <p>This tool will diagnose why user creation is failing in Active Directory.</p>
    <hr>";

// Connect to LDAP
$ldapconn = ldap_connect($LDAPServer, 389);
if (!$ldapconn) {
    die("<div class='error'>✗ Failed to connect to LDAP server: {$LDAPServer}</div></div></body></html>");
}

ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 30);

$ldapbind = @ldap_bind($ldapconn, $loginDN, $bind_password);
if (!$ldapbind) {
    die("<div class='error'>✗ Bind failed: " . ldap_error($ldapconn) . "</div></div></body></html>");
}

echo "<div class='success'>✓ Connected and bound successfully to {$LDAPServer}</div>";

// ==================== TEST 1: Domain Password Policy ====================
echo "<div class='section'>
<h2>📋 TEST 1: Domain Password Policy</h2>";

$root_dse = ldap_read($ldapconn, "", "(objectClass=*)", ['defaultNamingContext']);
if ($root_dse) {
    $entries = ldap_get_entries($ldapconn, $root_dse);
    $domain_dn = $entries[0]['defaultnamingcontext'][0];
    echo "Domain DN: <code>{$domain_dn}</code><br>";
    
    $domain_search = @ldap_read($ldapconn, $domain_dn, "(objectClass=domainDNS)", 
        ['minPwdLength', 'pwdProperties', 'pwdHistoryLength', 'lockoutDuration', 'lockoutThreshold', 'maxPwdAge']);
    
    if ($domain_search) {
        $domain_entries = ldap_get_entries($ldapconn, $domain_search);
        
        echo "<table>";
        echo "<tr><th>Policy</th><th>Value</th><th>Requirement</th></tr>";
        
        $min_length = isset($domain_entries[0]['minpwdlength'][0]) ? $domain_entries[0]['minpwdlength'][0] : '7 (default)';
        echo "<tr><td>Minimum Password Length</td><td>{$min_length}</td><td>Password must be at least {$min_length} characters</td></tr>";
        
        $pwd_props = isset($domain_entries[0]['pwdproperties'][0]) ? $domain_entries[0]['pwdproperties'][0] : 1;
        $complexity = ($pwd_props & 1) ? 'Yes' : 'No';
        echo "<tr><td>Password Complexity Required</td><td>{$complexity}</td><td>";
        if ($pwd_props & 1) {
            echo "✅ Requires: Uppercase, Lowercase, Numbers, Special Characters";
        } else {
            echo "⚠ No complexity required";
        }
        echo "</td></tr>";
        
        $max_age = isset($domain_entries[0]['maxpwdage'][0]) ? $domain_entries[0]['maxpwdage'][0] : 'Not set';
        if ($max_age != 'Not set' && $max_age > 0) {
            $days = round($max_age / 864000000000);
            echo "<tr><td>Maximum Password Age</td><td>{$days} days</td><td>Password expires after {$days} days</td></tr>";
        }
        
        echo "</table>";
    }
}

// ==================== TEST 2: OU Permissions ====================
echo "<div class='section'>
<h2>🔐 TEST 2: OU Permissions</h2>";

// Check if OU exists
$check_ou = @ldap_read($ldapconn, $target_ou, "(objectClass=*)", ['ou', 'objectClass']);
if (!$check_ou) {
    echo "<div class='test-fail'>✗ OU does not exist: {$target_ou}</div>";
    echo "<div class='warning'>Please create the OU first using the Moodle AD Setup tool.</div>";
} else {
    $ou_entries = ldap_get_entries($ldapconn, $check_ou);
    echo "<div class='test-pass'>✓ OU exists: {$target_ou}</div>";
    echo "ObjectClass: " . implode(', ', $ou_entries[0]['objectclass']) . "<br>";
    
    // Try to create a disabled test user (no password required)
    $test_user = "test_permission_" . time();
    $test_dn = "cn={$test_user},{$target_ou}";
    
    $test_data = [
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
        'cn' => $test_user,
        'sn' => 'Permission',
        'givenName' => 'Test',
        'sAMAccountName' => $test_user,
        'userAccountControl' => '514', // Disabled account - no password required
        'instanceType' => '4'
    ];
    
    echo "<br>Testing if bind user can create a DISABLED user (no password)...<br>";
    $test_result = @ldap_add($ldapconn, $test_dn, $test_data);
    
    if ($test_result) {
        echo "<div class='test-pass'>✓ SUCCESS! Can create disabled users. Permissions are OK!</div>";
        echo "The bind user has CREATE permissions on this OU.<br>";
        // Clean up
        ldap_delete($ldapconn, $test_dn);
        echo "Test user deleted.<br>";
    } else {
        $errno = ldap_errno($ldapconn);
        $error = ldap_error($ldapconn);
        echo "<div class='test-fail'>✗ Cannot create disabled user: Error {$errno}: {$error}</div>";
        
        if ($errno == 50) {
            echo "<div class='warning'>❌ The bind user lacks CREATE USER permissions on this OU.</div>";
            echo "<div class='info'>Solution: Grant 'Create User objects' permission to {$loginDN} on {$target_ou}</div>";
        } elseif ($errno == 53) {
            echo "<div class='warning'>⚠ Server unwilling - Possible objectClass restrictions on this OU</div>";
        }
    }
}

// ==================== TEST 3: Password Encoding ====================
echo "<div class='section'>
<h2>🔑 TEST 3: Password Encoding</h2>";

$test_passwords = [
    'Admin@12345678',
    'Test@2024!Pass',
    'P@ssw0rd123!',
    'Complex#Pass1',
    'Moodle@2024'
];

echo "<table>";
echo "<tr><th>Password</th><th>Length</th><th>Upper</th><th>Lower</th><th>Number</th><th>Special</th><th>Status</th></tr>";

foreach ($test_passwords as $pwd) {
    $has_upper = preg_match('/[A-Z]/', $pwd);
    $has_lower = preg_match('/[a-z]/', $pwd);
    $has_number = preg_match('/\d/', $pwd);
    $has_special = preg_match('/[@$!%*?&#]/', $pwd);
    $length = strlen($pwd);
    
    $valid = ($length >= 8 && $has_upper && $has_lower && $has_number && $has_special);
    
    echo "<tr>";
    echo "<td>{$pwd}</td>";
    echo "<td>{$length}</td>";
    echo "<td>" . ($has_upper ? '✅' : '❌') . "</td>";
    echo "<td>" . ($has_lower ? '✅' : '❌') . "</td>";
    echo "<td>" . ($has_number ? '✅' : '❌') . "</td>";
    echo "<td>" . ($has_special ? '✅' : '❌') . "</td>";
    echo "<td>" . ($valid ? "<span class='success'>Valid</span>" : "<span class='error'>Invalid</span>") . "</td>";
    echo "</tr>";
}
echo "</table>";

// ==================== TEST 4: Try Creating User with Different Passwords ====================
echo "<div class='section'>
<h2>🧪 TEST 4: Live User Creation Tests</h2>";

$test_results = [];

foreach ($test_passwords as $pwd) {
    $test_user = "testuser_" . time() . "_" . substr(md5($pwd), 0, 4);
    $test_dn = "cn={$test_user},{$target_ou}";
    
    $newpass = "\"" . $pwd . "\"";
    $newUnicodepass = mb_convert_encoding($newpass, 'UTF-16LE', 'UTF-8');
    
    $userdata = [
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
        'cn' => $test_user,
        'sn' => 'Test',
        'givenName' => 'User',
        'sAMAccountName' => $test_user,
        'userPrincipalName' => "{$test_user}@lbsnaa.gov.in",
        'userAccountControl' => '544',
        'unicodePwd' => $newUnicodepass,
        'instanceType' => '4'
    ];
    
    echo "<br>Testing password: <strong>{$pwd}</strong><br>";
    $result = @ldap_add($ldapconn, $test_dn, $userdata);
    
    if ($result) {
        echo "<div class='test-pass'>✅ SUCCESS! User created with password: {$pwd}</div>";
        // Clean up
        ldap_delete($ldapconn, $test_dn);
        echo "Test user deleted.<br>";
        $test_results[$pwd] = true;
        break; // Stop once we find a working password
    } else {
        $errno = ldap_errno($ldapconn);
        $error = ldap_error($ldapconn);
        echo "<div class='test-fail'>✗ Failed: Error {$errno} - {$error}</div>";
        $test_results[$pwd] = false;
        
        // Get extended error
        if (function_exists('ldap_get_option')) {
            $ext_error = null;
            ldap_get_option($ldapconn, LDAP_OPT_ERROR_STRING, $ext_error);
            if ($ext_error) {
                echo "<small>Extended: " . htmlspecialchars($ext_error) . "</small><br>";
            }
        }
    }
}

// ==================== TEST 5: Check Existing Users ====================
echo "<div class='section'>
<h2>👥 TEST 5: Existing Users in OU</h2>";

$search = @ldap_search($ldapconn, $target_ou, "(objectClass=user)", ['cn', 'sAMAccountName', 'userAccountControl'], 0, 10);
if ($search) {
    $entries = ldap_get_entries($ldapconn, $search);
    if ($entries['count'] > 0) {
        echo "Found {$entries['count']} users in {$target_ou}:<br>";
        echo "<ul>";
        for ($i = 0; $i < $entries['count']; $i++) {
            $cn = isset($entries[$i]['cn'][0]) ? $entries[$i]['cn'][0] : 'N/A';
            $sam = isset($entries[$i]['samaccountname'][0]) ? $entries[$i]['samaccountname'][0] : 'N/A';
            echo "<li><strong>{$sam}</strong> - {$cn}</li>";
        }
        echo "</ul>";
    } else {
        echo "No users found in this OU.<br>";
    }
} else {
    echo "Cannot search for users: " . ldap_error($ldapconn) . "<br>";
}

// ==================== TEST 6: Bind User Details ====================
echo "<div class='section'>
<h2>👤 TEST 6: Bind User Details</h2>";

$bind_user_search = @ldap_read($ldapconn, $loginDN, "(objectClass=*)", ['cn', 'memberOf', 'userAccountControl']);
if ($bind_user_search) {
    $entries = ldap_get_entries($ldapconn, $bind_user_search);
    echo "Bind User: {$loginDN}<br>";
    if (isset($entries[0]['memberof'])) {
        echo "Member of groups: <br><ul>";
        for ($i = 0; $i < $entries[0]['memberof']['count']; $i++) {
            echo "<li>" . $entries[0]['memberof'][$i] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "Not a member of any groups (or cannot read membership)<br>";
    }
}

// ==================== SUMMARY ====================
echo "<div class='section'>
<h2>📊 SUMMARY & RECOMMENDATIONS</h2>";

$all_pass = true;
foreach ($test_results as $pwd => $passed) {
    if ($passed) {
        echo "<div class='success'>✅ Found working password: {$pwd}</div>";
        echo "<div class='info'>Use this password format for user creation.</div>";
        $all_pass = false;
        break;
    }
}

if ($all_pass && !empty($test_results)) {
    echo "<div class='error'>❌ None of the test passwords worked.</div>";
    echo "<div class='warning'>Possible issues:</div>";
    echo "<ul>";
    echo "<li>The bind user lacks 'Create User objects' permission on the OU</li>";
    echo "<li>The OU has a Group Policy restricting user creation</li>";
    echo "<li>The domain has a very strict password policy</li>";
    echo "<li>The bind user needs 'Write all properties' permission</li>";
    echo "</ul>";
}

echo "<div class='info'>📌 Recommended Actions:</div>";
echo "<ol>";
echo "<li>If password tests failed, ask AD admin to check password policy</li>";
echo "<li>If create disabled user test failed, grant CREATE USER permission to bind user</li>";
echo "<li>Try using OU=Test-Ad,OU=LBSNAA... which may have fewer restrictions</li>";
echo "<li>Contact AD administrator with the error codes above</li>";
echo "</ol>";

ldap_close($ldapconn);

echo "<hr>
      <p><a href='?'>← Run Diagnostic Again</a> | 
         <a href='moodle_ad_setup.php'>← Back to Setup Tool</a></p>
</div>
</body>
</html>";
?>