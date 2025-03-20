<?php
$pageTitle = "Thank You";
require_once 'app/views/layout/header.php';

// Clear the session data
session_unset();
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body text-center">
                <h3 class="card-title mb-4">Thank You for Participating!</h3>
                <p class="card-text">
                    Your participation in this research study is greatly appreciated. Your responses will help us better understand 
                    the differences between human chaplain care and AI-based spiritual support.
                </p>
                <p class="card-text">
                    Your survey responses and chat data have been recorded. This information will be kept confidential and used only 
                    for research purposes.
                </p>
                <div class="mt-4">
                    <a href="index.php" class="btn btn-primary">Return to Home Page</a>
                </div>
                
                <div class="mt-5 pt-3 border-top">
                    <p class="text-muted small">
                        If you have any questions about this study, please contact the research team at 
                        <a href="mailto:<?php echo ADMIN_EMAIL; ?>"><?php echo ADMIN_EMAIL; ?></a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/layout/footer.php';
?>