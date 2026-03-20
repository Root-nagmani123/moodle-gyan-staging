<?php
// /local/form/test_ldap.php

require_once('../../config.php');

// Check if user is admin
require_login();
// require_capability('moodle/site:config', context_system::instance());

// Get the saved username from settings
$saved_username = get_config('local_form', 'ldap_test_username');
?>
<!DOCTYPE html>
<html>
<head>
    <title>LDAP Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-info { background-color: #d9edf7; border: 1px solid #bce8f1; color: #31708f; }
        .alert-success { background-color: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; }
        .alert-danger { background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; }
        .alert-warning { background-color: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .username-config { background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .username-config a { margin-left: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h2>LDAP User Test</h2>
    
    <?php if ($saved_username): ?>
        <div class="username-config alert alert-info">
            <strong>Testing username from settings:</strong> <?php echo htmlspecialchars($saved_username); ?>
            <a href="<?php echo new moodle_url('/admin/settings.php', ['section' => 'local_form_ldaptest']); ?>" target="_blank">
                Change username in settings
            </a>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <strong>No username configured!</strong> 
            Please go to <a href="<?php echo new moodle_url('/admin/settings.php', ['section' => 'local_form_ldaptest']); ?>" target="_blank">
            Local plugins → LDAP Test Settings</a> and enter a username to test.
        </div>
    <?php endif; ?>
    
    <hr>
    
    <?php
    // Get LDAP configuration correctly from auth_ldap plugin
    if ($saved_username) {
        // Get LDAP settings from the auth_ldap plugin
        $ldap_host = get_config('auth_ldap', 'host_url');
        $ldap_bind_dn = get_config('auth_ldap', 'bind_dn');
        $ldap_bind_pw = get_config('auth_ldap', 'bind_pw');
        $ldap_contexts = get_config('auth_ldap', 'contexts');
        $ldap_user_attribute = get_config('auth_ldap', 'user_attribute');
        $ldap_version = get_config('auth_ldap', 'ldap_version');
        
        echo "<h3>Current LDAP Configuration:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Setting</th><th>Value</th></tr>";
        echo "<tr><td>Host URL</td><td>" . ($ldap_host ?: 'Not set') . "</td></tr>";
        echo "<tr><td>Bind DN</td><td>" . ($ldap_bind_dn ?: 'Not set') . "</td></tr>";
        echo "<tr><td>Bind Password</td><td>" . ($ldap_bind_pw ? '********' : 'Not set') . "</td></tr>";
        echo "<tr><td>Contexts</td><td>" . ($ldap_contexts ?: 'Not set') . "</td></tr>";
        echo "<tr><td>User Attribute</td><td>" . ($ldap_user_attribute ?: 'Not set') . "</td></tr>";
        echo "<tr><td>LDAP Version</td><td>" . ($ldap_version ?: 'Not set') . "</td></tr>";
        echo "</table><br>";
        
        // Check if LDAP is configured
        if (empty($ldap_host) || empty($ldap_bind_dn) || empty($ldap_bind_pw)) {
            echo "<div class='alert alert-danger'>";
            echo "<strong>ERROR: LDAP is not properly configured!</strong><br>";
            echo "Please configure LDAP authentication in:<br>";
            echo "Site Administration → Plugins → Authentication → LDAP server";
            echo "</div>";
        } else {
            // Connect to LDAP
            $ldapconn = ldap_connect($ldap_host);
            if (!$ldapconn) {
                echo "<div class='alert alert-danger'>Failed to connect to LDAP server: " . $ldap_host . "</div>";
            } else {
                // Set LDAP options
                ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, $ldap_version ?: 3);
                ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
                
                // Bind to LDAP
                $ldapbind = @ldap_bind($ldapconn, $ldap_bind_dn, $ldap_bind_pw);
                if (!$ldapbind) {
                    echo "<div class='alert alert-danger'>";
                    echo "<strong>Bind failed:</strong> " . ldap_error($ldapconn) . "<br>";
                    echo "Please check your Bind DN and Password in LDAP server settings.";
                    echo "</div>";
                } else {
                    echo "<div class='alert alert-success'>✓ Connected and bound successfully to LDAP server</div><br>";
                    
                    $test_username = $saved_username;
                    echo "<h3>Checking username: <strong>" . htmlspecialchars($test_username) . "</strong></h3>";
                    
                    // Try multiple contexts
                    $contexts_to_try = [];
                    
                    // Add configured contexts
                    if (!empty($ldap_contexts)) {
                        $contexts_to_try = array_merge($contexts_to_try, explode(';', $ldap_contexts));
                    }
                    
                    // Add common contexts as fallback
                    $contexts_to_try[] = "cn=Users,dc=lbsnaa,dc=gov,dc=in";
                    $contexts_to_try[] = "ou=Users,dc=lbsnaa,dc=gov,dc=in";
                    $contexts_to_try[] = "dc=lbsnaa,dc=gov,dc=in";
                    
                    $contexts_to_try = array_unique($contexts_to_try);
                    $found = false;
                    
                    foreach ($contexts_to_try as $context) {
                        if (empty($context)) continue;
                        
                        echo "<p>Searching in context: <strong>{$context}</strong><br>";
                        $search_filter = "(&(objectClass=user)({$ldap_user_attribute}={$test_username}))";
                        echo "Filter: {$search_filter}</p>";
                        
                        $search_result = @ldap_search($ldapconn, $context, $search_filter, ['cn', 'samaccountname', 'givenName', 'sn', 'mail', 'userPrincipalName']);
                        
                        if ($search_result) {
                            $entries = ldap_get_entries($ldapconn, $search_result);
                            
                            if ($entries['count'] > 0) {
                                echo "<div class='alert alert-success'>";
                                echo "<strong>✓ USER EXISTS in Active Directory!</strong><br>";
                                echo "Found in context: {$context}";
                                echo "</div>";
                                
                                echo "<h4>User Details:</h4>";
                                echo "<table border='1' cellpadding='5'>";
                                echo "<tr><th>Attribute</th><th>Value</th></tr>";
                                echo "<tr><td>CN (Common Name)</td><td>" . $entries[0]['cn'][0] . "</td></tr>";
                                echo "<tr><td>sAMAccountName</td><td>" . $entries[0]['samaccountname'][0] . "</td></tr>";
                                echo "<tr><td>Given Name</td><td>" . (isset($entries[0]['givenname'][0]) ? $entries[0]['givenname'][0] : 'N/A') . "</td></tr>";
                                echo "<tr><td>Last Name (SN)</td><td>" . (isset($entries[0]['sn'][0]) ? $entries[0]['sn'][0] : 'N/A') . "</td></tr>";
                                echo "<tr><td>Email</td><td>" . (isset($entries[0]['mail'][0]) ? $entries[0]['mail'][0] : 'N/A') . "</td></tr>";
                                echo "<tr><td>User Principal Name</td><td>" . (isset($entries[0]['userprincipalname'][0]) ? $entries[0]['userprincipalname'][0] : 'N/A') . "</td></tr>";
                                echo "</table>";
                                
                                $found = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$found) {
                        echo "<div class='alert alert-danger'>";
                        echo "<strong>✗ USER NOT FOUND in Active Directory</strong><br>";
                        echo "The username '" . htmlspecialchars($test_username) . "' does not exist in the AD directory.<br>";
                        echo "Please check:<br>";
                        echo "- The username is spelled correctly<br>";
                        echo "- The user exists in the LDAP directory<br>";
                        echo "- The contexts setting is correct<br>";
                        echo "- The user attribute ('{$ldap_user_attribute}') is correct";
                        echo "</div>";
                    }
                    
                    // Show sample users
                    echo "<h3>Sample Users from AD (first 5):</h3>";
                    $search_filter = "(objectClass=user)";
                    $search_result = ldap_search($ldapconn, "cn=Users,dc=lbsnaa,dc=gov,dc=in", $search_filter, ['samaccountname', 'cn'], 0, 5);
                    
                    if ($search_result) {
                        $entries = ldap_get_entries($ldapconn, $search_result);
                        if ($entries['count'] > 0) {
                            echo "Found " . $entries['count'] . " sample users:<br>";
                            echo "<ul>";
                            for ($i = 0; $i < $entries['count']; $i++) {
                                $username = isset($entries[$i]['samaccountname'][0]) ? $entries[$i]['samaccountname'][0] : 'N/A';
                                $cn = isset($entries[$i]['cn'][0]) ? $entries[$i]['cn'][0] : 'N/A';
                                echo "<li><strong>{$username}</strong> - {$cn}</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "No users found in cn=Users,dc=lbsnaa,dc=gov,dc=in<br>";
                        }
                    } else {
                        echo "Cannot search for sample users: " . ldap_error($ldapconn);
                    }
                    
                    ldap_close($ldapconn);
                }
            }
        }
    }
    ?>
    
    <hr>
    <p><a href="<?php echo new moodle_url('/admin/settings.php', ['section' => 'local_form_ldaptest']); ?>" class="btn btn-default">
        ← Back to LDAP Test Settings
    </a></p>
</div>
</body>
</html>