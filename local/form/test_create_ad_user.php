<?php
// /local/form/test_create_ad_user.php

require_once('../../config.php');

// Check if user is admin
require_login();
require_capability('moodle/site:config', context_system::instance());

// Get the username to test
$test_username = optional_param('username', 'testuser8', PARAM_TEXT);
$test_password = optional_param('password', 'Admin@123', PARAM_TEXT);
$test_firstname = optional_param('firstname', 'Test', PARAM_TEXT);
$test_lastname = optional_param('lastname', 'User', PARAM_TEXT);
$test_phone = optional_param('phone', '9999999999', PARAM_TEXT);
$test_email = optional_param('email', 'test@test.com', PARAM_TEXT);

echo "<h2>Test Creating AD User (Matching Java Implementation)</h2>";

echo "<form method='post'>";
echo "Username: <input type='text' name='username' value='{$test_username}'><br>";
echo "First Name: <input type='text' name='firstname' value='{$test_firstname}'><br>";
echo "Last Name: <input type='text' name='lastname' value='{$test_lastname}'><br>";
echo "Password: <input type='password' name='password' value='{$test_password}'><br>";
echo "Email: <input type='text' name='email' value='{$test_email}'><br>";
echo "Phone: <input type='text' name='phone' value='{$test_phone}'><br>";
echo "<input type='submit' value='Test Create User (Java Style)'>";
echo "</form><br><hr><br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // EXACT MATCH to Java LDAPAddUser class
    $LDAPServer = "103.225.204.25";  // Java uses this IP
    $LDAPPORT = 389;
    $loginDN = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
    $password = "lbsnaa123";
    $ldapVersion = 3;
    $BIND_TIMEOUT = 20000; // 20 seconds
    
    // Container from Java (MOST IMPORTANT!)
    $containerName = "ou=FC97,ou=LBSNAA,dc=lbsnaa,dc=gov,dc=in";
    
    // User data (matches Java exactly)
    $givenname = $test_firstname . " " . $test_lastname;
    $first_name = $test_firstname;
    $last_name = $test_lastname;
    $contactNo = $test_phone;
    $email = $test_email;
    $user_name = $test_username;
    $user_password = $test_password;
    
    echo "<h3>Java LDAPAddUser Configuration:</h3>";
    echo "LDAPServer: {$LDAPServer}<br>";
    echo "LDAPPORT: {$LDAPPORT}<br>";
    echo "loginDN: {$loginDN}<br>";
    echo "containerName: {$containerName}<br>";
    echo "DN: cn={$givenname},{$containerName}<br><br>";
    
    // Connect to LDAP (matches Java: conn.connect(LDAPServer, LDAPPORT))
    $ldapconn = ldap_connect($LDAPServer, $LDAPPORT);
    if (!$ldapconn) {
        echo "<div class='alert alert-danger'>Failed to connect to LDAP server</div>";
        exit;
    }
    
    // Set options (matches Java: cons.setTimeLimit(BIND_TIMEOUT))
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, $ldapVersion);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, $BIND_TIMEOUT / 1000);
    
    // Bind (matches Java: conn.bind(ldapVersion, loginDN, password.getBytes("UTF8")))
    $ldapbind = @ldap_bind($ldapconn, $loginDN, $password);
    if (!$ldapbind) {
        echo "<div class='alert alert-danger'>Bind failed: " . ldap_error($ldapconn) . "</div>";
        ldap_close($ldapconn);
        exit;
    }
    
    echo "<div class='alert alert-success'>✓ Connected and bound successfully (Java style)</div><br>";
    
    // Encode password EXACTLY like Java
    // Java: String newpass = "\""+user_password+"\"";
    // Java: byte[] newUnicodepass = newpass.getBytes("UTF-16LE");
    $newpass = "\"" . $user_password . "\"";
    $newUnicodepass = mb_convert_encoding($newpass, 'UTF-16LE', 'UTF-8');
    
    // Build attributes EXACTLY like Java LDAPAttributeSet
    // Java order: objectclass, givenname, sn, telephonenumber, mail, sAMAccountName, userPrincipalName, userAccountControl, pwdLastSet, unicodePwd
    $attribSet = [
        // Java: new LDAPAttribute("objectclass", new String("inetOrgPerson"))
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user', 'inetOrgPerson'],
        
        // Java: new LDAPAttribute("givenname", new String(""+first_name+""))
        'givenName' => $first_name,
        
        // Java: new LDAPAttribute("sn", new String(""+last_name+""))
        'sn' => $last_name,
        
        // Java: new LDAPAttribute("telephonenumber", new String(""+contactNo+""))
        'telephoneNumber' => $contactNo,
        
        // Java: new LDAPAttribute("mail", new String(""+email+""))
        'mail' => $email,
        
        // Java: new LDAPAttribute("sAMAccountName", new String(""+user_name+""))
        'sAMAccountName' => $user_name,
        
        // Java: new LDAPAttribute("userPrincipalName", new String(""+user_name+""))
        'userPrincipalName' => $user_name,
        
        // Java: new LDAPAttribute("userAccountControl", new String("553"))
        'userAccountControl' => '553',
        
        // Java: new LDAPAttribute("pwdLastSet", new String("-1"))
        'pwdLastSet' => '-1',
        
        // Java: new LDAPAttribute("unicodePwd", newUnicodepass)
        'unicodePwd' => $newUnicodepass,
        
        // Additional required attributes
        'cn' => $givenname,
        'displayName' => $givenname,
        'instanceType' => '4',
        'accountExpires' => '0'
    ];
    
    // DN exactly like Java: dn ="cn="+givenname+","+containerName
    $dn = "cn={$givenname},{$containerName}";
    
    echo "<h3>Attempting to create user (Java style)...</h3>";
    echo "DN: {$dn}<br>";
    echo "Givenname: {$givenname}<br>";
    echo "First Name: {$first_name}<br>";
    echo "Last Name: {$last_name}<br>";
    echo "Username: {$user_name}<br><br>";
    
    // Check if user already exists in this container
    $search_filter = "(&(objectClass=user)(cn={$givenname}))";
    $search_result = @ldap_search($ldapconn, $containerName, $search_filter, ['cn']);
    
    if ($search_result) {
        $entries = ldap_get_entries($ldapconn, $search_result);
        if ($entries['count'] > 0) {
            echo "<div class='alert alert-warning'>⚠ User '{$givenname}' already exists in container!</div>";
            ldap_close($ldapconn);
            exit;
        }
    }
    
    // Create user (matches Java: conn.add(newEntry))
    $result = @ldap_add($ldapconn, $dn, $attribSet);
    
    if ($result) {
        echo "<div class='alert alert-success'>✓ SUCCESS! User created in AD (Java style)!</div>";
        echo "User DN: {$dn}<br>";
        echo "Username: {$user_name}<br>";
        echo "Given Name: {$givenname}<br>";
        
        // Verify creation
        $verify = @ldap_search($ldapconn, $containerName, $search_filter, ['cn', 'sAMAccountName']);
        if ($verify) {
            $entries = ldap_get_entries($ldapconn, $verify);
            if ($entries['count'] > 0) {
                echo "<div class='alert alert-success'>✓ Verified: User exists in AD container!</div>";
            }
        }
        
        // Option to delete
        echo "<br><form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='username' value='{$user_name}'>";
        echo "<input type='hidden' name='firstname' value='{$first_name}'>";
        echo "<input type='hidden' name='lastname' value='{$last_name}'>";
        echo "<input type='hidden' name='password' value='{$user_password}'>";
        echo "<input type='hidden' name='delete_user' value='1'>";
        echo "<input type='submit' value='Delete Test User' style='background-color:#dc3545;color:white;padding:5px 10px;border:none;border-radius:3px;'>";
        echo "</form>";
        
    } else {
        $error = ldap_error($ldapconn);
        $errno = ldap_errno($ldapconn);
        echo "<div class='alert alert-danger'>✗ FAILED to create user (Java style)</div>";
        echo "Error {$errno}: {$error}<br>";
        
        if ($errno == 50) {
            echo "<br><strong>SOLUTION:</strong> The bind user lacks write permissions on container: {$containerName}<br>";
            echo "Contact AD administrator to grant 'Create User objects' permission to: <strong>{$loginDN}</strong>";
        } elseif ($errno == 53) {
            echo "<br><strong>Error 53 - Server unwilling to perform. Possible issues:</strong><br>";
            echo "<ul>";
            echo "<li>Container '{$containerName}' may not exist</li>";
            echo "<li>Check if the OU 'FC97' exists under 'LBSNAA'</li>";
            echo "<li>The bind user may not have permissions on this specific OU</li>";
            echo "</ul>";
        }
    }
    
    ldap_close($ldapconn);
}

// Handle delete (matches Java cleanup)
if (isset($_POST['delete_user']) && !empty($_POST['username'])) {
    $username_to_delete = $_POST['username'];
    $givenname_to_delete = $_POST['firstname'] . " " . $_POST['lastname'];
    $containerName = "ou=FC97,ou=LBSNAA,dc=lbsnaa,dc=gov,dc=in";
    $dn = "cn={$givenname_to_delete},{$containerName}";
    
    $ldapconn = ldap_connect("103.225.204.25", 389);
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    $ldapbind = @ldap_bind($ldapconn, "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in", "lbsnaa123");
    
    if ($ldapbind) {
        if (ldap_delete($ldapconn, $dn)) {
            echo "<div class='alert alert-info'>User '{$username_to_delete}' deleted from AD container.</div>";
        } else {
            echo "<div class='alert alert-warning'>Failed to delete user: " . ldap_error($ldapconn) . "</div>";
        }
    }
    ldap_close($ldapconn);
}
?>