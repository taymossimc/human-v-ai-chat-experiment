<?php
$pageTitle = "Error";
require_once 'app/views/layout/header.php';

// Get error code and message
$errorCode = isset($_GET['code']) ? (int)$_GET['code'] : 500;
$errorMessage = isset($_GET['message']) ? sanitize($_GET['message']) : 'An error occurred.';

// Set default error message based on code
if (!isset($_GET['message'])) {
    switch ($errorCode) {
        case 404:
            $errorMessage = 'The page you requested could not be found.';
            break;
        case 403:
            $errorMessage = 'You do not have permission to access this page.';
            break;
        case 401:
            $errorMessage = 'Authentication is required to access this page.';
            break;
        default:
            $errorMessage = 'An unexpected error occurred. Please try again later.';
            break;
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body text-center">
                <h3 class="card-title text-danger mb-4">Error <?php echo $errorCode; ?></h3>
                <p class="card-text">
                    <?php echo $errorMessage; ?>
                </p>
                <div class="mt-4">
                    <a href="index.php" class="btn btn-primary">Return to Home Page</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/layout/footer.php';
?> 