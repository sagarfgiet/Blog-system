<?php
require_once 'includes/functions.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Login';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $errors['general'] = 'Please enter both email and password';
    } else {
        try {
            $db = getDB();
            
            // Using named parameters for better readability
            $stmt = $db->prepare("SELECT id, username, email, password, is_admin FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                
                // Set flash message
                $_SESSION['flash_message'] = 'Welcome back, ' . htmlspecialchars($user['username']) . '!';
                $_SESSION['flash_class'] = 'alert-success';
                
                // Redirect to home
                redirect('index.php');
            } else {
                $errors['general'] = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $errors['general'] = 'Login failed. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Login to Your Account</h4>
            </div>
            <div class="card-body">
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo $_POST['email'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                        <p><a href="forgot-password.php">Forgot your password?</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>