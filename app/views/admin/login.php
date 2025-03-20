<?php
$pageTitle = "Admin Login";
require_once 'app/views/layout/header.php';

// Include database and helper functions
require_once 'app/database/database.php';
require_once 'app/utils/helpers.php';

// If admin is already logged in, redirect to admin page
if (isAdminLoggedIn()) {
    redirect('index.php?page=admin');
}

// Initialize database connection
$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    
    // Process if no errors
    if (empty($errors)) {
        // Get admin user
        $adminUser = $db->fetchOne(
            "SELECT * FROM admin_users WHERE username = ?",
            [$username]
        );
        
        if ($adminUser && password_verify($password, $adminUser['password_hash'])) {
            // Login successful
            $_SESSION['admin_id'] = $adminUser['id'];
            $_SESSION['admin_username'] = $adminUser['username'];
            $_SESSION['admin_role'] = $adminUser['role'];
            
            // Redirect to intended page or admin dashboard
            if (isset($_SESSION['admin_redirect'])) {
                $redirect = $_SESSION['admin_redirect'];
                unset($_SESSION['admin_redirect']);
                redirect($redirect);
            } else {
                redirect('index.php?page=admin');
            }
        } else {
            // Login failed
            $errors[] = "Invalid username or password.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title mb-4">Admin Login</h3>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="index.php?page=admin_login">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username:</label>
                        <input type="text" class="form-control" id="username" name="username" required 
                                value="<?php echo isset($username) ? $username : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <p class="small text-muted">For development, use:<br> Username: admin<br>Password: admin123</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/layout/footer.php';
?> 