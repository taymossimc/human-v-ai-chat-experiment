<?php
$pageTitle = "Consent Form";
require_once 'app/views/layout/header.php';

// Include database and helper functions
require_once 'app/database/database.php';
require_once 'app/utils/helpers.php';

// Initialize database connection
$db = Database::getInstance();

// Get consent policy text
$consentText = getActiveConsentPolicy($db);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $consent = isset($_POST['consent']) ? 1 : 0;
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!validateEmail($email)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (!$consent) {
        $errors[] = "You must provide consent to participate in the study.";
    }
    
    // Process if no errors
    if (empty($errors)) {
        try {
            // Begin transaction
            $db->beginTransaction();
            
            // Insert participant
            $participantId = $db->insert(
                "INSERT INTO participants (name, email, consent_given, consent_timestamp) VALUES (?, ?, ?, NOW())",
                [$name, $email, $consent]
            );
            
            // Randomize participant to AI or human condition
            $sessionType = randomizeParticipant();
            
            // Create session
            $sessionId = $db->insert(
                "INSERT INTO sessions (participant_id, session_type, start_time, status) VALUES (?, ?, NOW(), 'created')",
                [$participantId, $sessionType]
            );
            
            // Commit transaction
            $db->commit();
            
            // Store session info in PHP session
            $_SESSION['participant_id'] = $participantId;
            $_SESSION['session_id'] = $sessionId;
            $_SESSION['session_type'] = $sessionType;
            
            // Redirect to pre-survey
            redirect('index.php?page=pre_survey');
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            
            if (DEBUG) {
                $errors[] = "An error occurred: " . $e->getMessage();
            } else {
                $errors[] = "An error occurred. Please try again later.";
            }
            
            logError("Consent form error: " . $e->getMessage());
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title mb-4">Consent Form / Privacy Policy</h3>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="consent-text p-3 mb-4 bg-light rounded">
                    <p><?php echo $consentText; ?></p>
                </div>
                
                <form method="POST" action="index.php?page=consent">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name:</label>
                        <input type="text" class="form-control" id="name" name="name" required 
                                value="<?php echo isset($name) ? $name : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address:</label>
                        <input type="email" class="form-control" id="email" name="email" required
                                value="<?php echo isset($email) ? $email : ''; ?>">
                    </div>
                    
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="consent" name="consent" required>
                        <label class="form-check-label" for="consent">
                            I have read and understood the information above and consent to participate in this research study.
                        </label>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Submit Consent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/layout/footer.php';
?> 