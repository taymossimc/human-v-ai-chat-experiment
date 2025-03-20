<?php
$pageTitle = "Pre-Chat Survey";
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

// Get survey questions
$questions = getSurveyQuestions($db, 'pre');

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
                "INSERT INTO pre_survey_responses (session_id, question_id, answer) VALUES (?, ?, ?)",
                [$sessionId, $questionId, $answer]
            );
        }
        
        // Update session status
        $db->update(
            "UPDATE sessions SET status = 'pre_survey_completed' WHERE id = ?",
            [$sessionId]
        );
        
        // Commit transaction
        $db->commit();
        
        // Redirect to chat page
        redirect('index.php?page=chat');
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        
        if (DEBUG) {
            $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
        } else {
            $_SESSION['error_message'] = "An error occurred. Please try again later.";
        }
        
        logError("Pre-survey error: " . $e->getMessage());
    }
}

// Insert sample questions if none exist (for development)
if (empty($questions) && DEBUG) {
    // Sample pre-survey questions
    $sampleQuestions = [
        [
            'survey_type' => 'pre',
            'question_text' => 'How would you rate your current spiritual well-being?',
            'question_type' => 'likert',
            'options' => json_encode(['Very Poor', 'Poor', 'Neutral', 'Good', 'Very Good']),
            'required' => 1,
            'order_num' => 1
        ],
        [
            'survey_type' => 'pre',
            'question_text' => 'Do you practice any particular faith tradition?',
            'question_type' => 'radio',
            'options' => json_encode(['Christianity', 'Judaism', 'Islam', 'Buddhism', 'Hinduism', 'Spiritual but not religious', 'Atheist/Agnostic', 'Other']),
            'required' => 1,
            'order_num' => 2
        ],
        [
            'survey_type' => 'pre',
            'question_text' => 'What topics would you like to discuss in your spiritual care conversation today? (Select all that apply)',
            'question_type' => 'checkbox',
            'options' => json_encode(['Meaning and purpose', 'Grief and loss', 'Forgiveness', 'Faith doubts', 'Prayer/meditation', 'Religious practices', 'Ethical concerns', 'Other']),
            'required' => 1,
            'order_num' => 3
        ],
        [
            'survey_type' => 'pre',
            'question_text' => 'If you selected "Other" above, please specify:',
            'question_type' => 'text',
            'options' => null,
            'required' => 0,
            'order_num' => 4
        ],
        [
            'survey_type' => 'pre',
            'question_text' => 'What are your expectations for this spiritual care conversation?',
            'question_type' => 'textarea',
            'options' => null,
            'required' => 1,
            'order_num' => 5
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
    $questions = getSurveyQuestions($db, 'pre');
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title mb-4">Pre-Chat Survey</h3>
                <p class="card-text mb-4">Please complete this brief survey before beginning your chat conversation.</p>
                
                <?php if (empty($questions)): ?>
                <div class="alert alert-warning">
                    No survey questions are available at this time. Please contact the administrator.
                </div>
                <?php else: ?>
                <form method="POST" action="index.php?page=pre_survey" class="survey-form">
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
                        <button type="submit" class="btn btn-primary">Submit and Continue to Chat</button>
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