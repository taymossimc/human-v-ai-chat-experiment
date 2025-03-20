<?php
$pageTitle = "Post-Chat Survey";
require_once 'app/views/layout/header.php';

// Include database and helper functions
require_once 'app/database/database.php';
require_once 'app/utils/helpers.php';

// Check if user has a valid session
if (!isset($_SESSION['participant_id']) || !isset($_SESSION['session_id'])) {
    redirect('index.php');
}

// Initialize database connection
$db = Database::getInstance();

// Get the participant's session ID
$sessionId = $_SESSION['session_id'];
$sessionType = $_SESSION['session_type'];

// Check if chat is completed
$session = $db->fetchOne(
    "SELECT * FROM sessions WHERE id = ? AND status = 'chat_completed'",
    [$sessionId]
);

if (!$session) {
    redirect('index.php?page=chat');
}

// Get survey questions
$questions = getSurveyQuestions($db, 'post');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Process each survey question response
        foreach ($questions as $question) {
            $questionId = $question['id'];
            $answer = '';
            
            // Handle different question types
            switch ($question['question_type']) {
                case 'radio':
                case 'text':
                case 'textarea':
                    $answer = sanitize($_POST['question_' . $questionId] ?? '');
                    break;
                    
                case 'checkbox':
                    // Combine multiple checkbox values into a JSON array
                    $checkboxValues = isset($_POST['question_' . $questionId]) ? $_POST['question_' . $questionId] : [];
                    $answer = json_encode($checkboxValues);
                    break;
                    
                case 'likert':
                    $answer = sanitize($_POST['question_' . $questionId] ?? '');
                    break;
            }
            
            // Skip if answer is empty and question is not required
            if (empty($answer) && !$question['required']) {
                continue;
            }
            
            // Insert the survey response
            $db->insert(
                "INSERT INTO post_survey_responses (session_id, question_id, answer) VALUES (?, ?, ?)",
                [$sessionId, $questionId, $answer]
            );
        }
        
        // Update session status
        $db->update(
            "UPDATE sessions SET status = 'post_survey_completed' WHERE id = ?",
            [$sessionId]
        );
        
        // Commit transaction
        $db->commit();
        
        // Redirect to thank you page
        redirect('index.php?page=thank_you');
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        
        if (DEBUG) {
            $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
        } else {
            $_SESSION['error_message'] = "An error occurred. Please try again later.";
        }
        
        logError("Post-survey error: " . $e->getMessage());
    }
}

// Insert sample questions if none exist (for development)
if (empty($questions) && DEBUG) {
    // Sample post-survey questions
    $sampleQuestions = [
        [
            'survey_type' => 'post',
            'question_text' => 'How would you rate your overall experience with the spiritual care conversation?',
            'question_type' => 'likert',
            'options' => json_encode(['Very Poor', 'Poor', 'Neutral', 'Good', 'Very Good']),
            'required' => 1,
            'order_num' => 1
        ],
        [
            'survey_type' => 'post',
            'question_text' => 'Did you feel listened to and understood during the conversation?',
            'question_type' => 'likert',
            'options' => json_encode(['Not at all', 'Slightly', 'Moderately', 'Very much', 'Completely']),
            'required' => 1,
            'order_num' => 2
        ],
        [
            'survey_type' => 'post',
            'question_text' => 'Did the conversation provide you with spiritual support?',
            'question_type' => 'likert',
            'options' => json_encode(['Not at all', 'Slightly', 'Moderately', 'Very much', 'Completely']),
            'required' => 1,
            'order_num' => 3
        ],
        [
            'survey_type' => 'post',
            'question_text' => 'How authentic did the conversation feel?',
            'question_type' => 'likert',
            'options' => json_encode(['Not at all authentic', 'Slightly authentic', 'Moderately authentic', 'Very authentic', 'Completely authentic']),
            'required' => 1,
            'order_num' => 4
        ],
        [
            'survey_type' => 'post',
            'question_text' => 'Did you feel comfortable sharing your spiritual concerns?',
            'question_type' => 'likert',
            'options' => json_encode(['Not at all comfortable', 'Slightly comfortable', 'Moderately comfortable', 'Very comfortable', 'Completely comfortable']),
            'required' => 1,
            'order_num' => 5
        ],
        [
            'survey_type' => 'post',
            'question_text' => 'Would you use this service again in the future?',
            'question_type' => 'radio',
            'options' => json_encode(['Yes', 'No', 'Maybe']),
            'required' => 1,
            'order_num' => 6
        ],
        [
            'survey_type' => 'post',
            'question_text' => 'What did you like most about the conversation?',
            'question_type' => 'textarea',
            'options' => null,
            'required' => 1,
            'order_num' => 7
        ],
        [
            'survey_type' => 'post',
            'question_text' => 'What could be improved about the conversation?',
            'question_type' => 'textarea',
            'options' => null,
            'required' => 1,
            'order_num' => 8
        ],
        [
            'survey_type' => 'post',
            'question_text' => 'Any additional comments or feedback?',
            'question_type' => 'textarea',
            'options' => null,
            'required' => 0,
            'order_num' => 9
        ]
    ];
    
    // Insert sample questions
    foreach ($sampleQuestions as $question) {
        $db->insert(
            "INSERT INTO survey_questions (survey_type, question_text, question_type, options, required, order_num) VALUES (?, ?, ?, ?, ?, ?)",
            [$question['survey_type'], $question['question_text'], $question['question_type'], $question['options'], $question['required'], $question['order_num']]
        );
    }
    
    // Refresh questions
    $questions = getSurveyQuestions($db, 'post');
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title mb-4">Post-Chat Survey</h3>
                <p class="card-text mb-4">Please complete this brief survey about your chat experience. Your feedback is valuable to our research.</p>
                
                <?php if (empty($questions)): ?>
                <div class="alert alert-warning">
                    No survey questions are available at this time. Please contact the administrator.
                </div>
                <?php else: ?>
                <form method="POST" action="index.php?page=post_survey" class="survey-form">
                    <?php foreach ($questions as $question): ?>
                    <div class="mb-4 survey-question">
                        <label class="form-label fw-bold">
                            <?php echo $question['question_text']; ?>
                            <?php if ($question['required']): ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>
                        
                        <?php switch($question['question_type']): 
                              case 'text': ?>
                            <input type="text" class="form-control" name="question_<?php echo $question['id']; ?>" 
                                   <?php echo $question['required'] ? 'required' : ''; ?>>
                        <?php break; ?>
                        
                        <?php case 'textarea': ?>
                            <textarea class="form-control" name="question_<?php echo $question['id']; ?>" rows="3"
                                     <?php echo $question['required'] ? 'required' : ''; ?>></textarea>
                        <?php break; ?>
                        
                        <?php case 'radio': 
                              $options = json_decode($question['options'], true); ?>
                            <?php foreach ($options as $index => $option): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="question_<?php echo $question['id']; ?>" 
                                       id="question_<?php echo $question['id']; ?>_<?php echo $index; ?>" value="<?php echo $option; ?>"
                                       <?php echo $question['required'] ? 'required' : ''; ?>>
                                <label class="form-check-label" for="question_<?php echo $question['id']; ?>_<?php echo $index; ?>">
                                    <?php echo $option; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php break; ?>
                        
                        <?php case 'checkbox': 
                              $options = json_decode($question['options'], true); ?>
                            <?php foreach ($options as $index => $option): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="question_<?php echo $question['id']; ?>[]" 
                                       id="question_<?php echo $question['id']; ?>_<?php echo $index; ?>" value="<?php echo $option; ?>">
                                <label class="form-check-label" for="question_<?php echo $question['id']; ?>_<?php echo $index; ?>">
                                    <?php echo $option; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php break; ?>
                        
                        <?php case 'likert': 
                              $options = json_decode($question['options'], true); ?>
                            <div class="likert-scale">
                                <?php foreach ($options as $index => $option): ?>
                                <div class="likert-option">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="question_<?php echo $question['id']; ?>" 
                                               id="question_<?php echo $question['id']; ?>_<?php echo $index; ?>" value="<?php echo $option; ?>"
                                               <?php echo $question['required'] ? 'required' : ''; ?>>
                                        <label class="form-check-label" for="question_<?php echo $question['id']; ?>_<?php echo $index; ?>">
                                            <?php echo $option; ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php break; ?>
                        <?php endswitch; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary">Submit and Complete</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/layout/footer.php';
?> 