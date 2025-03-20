<?php
$pageTitle = "Export Data";
require_once 'app/views/layout/header.php';

// Include database and helper functions
require_once 'app/database/database.php';
require_once 'app/utils/helpers.php';

// Check if user is logged in as admin
if (!isAdminLoggedIn()) {
    redirect('index.php?page=admin_login');
}

// Initialize database connection
$db = Database::getInstance();

// Handle export requests
if (isset($_GET['export'])) {
    $exportType = sanitize($_GET['export']);
    
    switch ($exportType) {
        case 'participants':
            // Export participant list
            $data = $db->fetchAll(
                "SELECT p.id, p.name, p.email, p.consent_given, p.consent_timestamp, p.created_at, 
                        s.id as session_id, s.session_type, s.start_time, s.end_time, s.status 
                 FROM participants p 
                 LEFT JOIN sessions s ON p.id = s.participant_id 
                 ORDER BY p.id ASC"
            );
            
            exportToJson($data, 'participants.json');
            break;
            
        case 'pre_survey':
            // Export pre-survey responses
            $data = $db->fetchAll(
                "SELECT ps.id, ps.session_id, ps.question_id, ps.answer, ps.created_at,
                        p.name as participant_name, p.email as participant_email,
                        sq.question_text, sq.question_type, s.session_type
                 FROM pre_survey_responses ps
                 JOIN sessions s ON ps.session_id = s.id
                 JOIN participants p ON s.participant_id = p.id
                 JOIN survey_questions sq ON ps.question_id = sq.id
                 ORDER BY ps.session_id ASC, sq.order_num ASC"
            );
            
            exportToJson($data, 'pre_survey_responses.json');
            break;
            
        case 'post_survey':
            // Export post-survey responses
            $data = $db->fetchAll(
                "SELECT ps.id, ps.session_id, ps.question_id, ps.answer, ps.created_at,
                        p.name as participant_name, p.email as participant_email,
                        sq.question_text, sq.question_type, s.session_type
                 FROM post_survey_responses ps
                 JOIN sessions s ON ps.session_id = s.id
                 JOIN participants p ON s.participant_id = p.id
                 JOIN survey_questions sq ON ps.question_id = sq.id
                 ORDER BY ps.session_id ASC, sq.order_num ASC"
            );
            
            exportToJson($data, 'post_survey_responses.json');
            break;
            
        case 'chat_logs':
            // Export chat logs
            $data = $db->fetchAll(
                "SELECT cm.id, cm.chat_session_id, cm.sender_type, cm.message, cm.timestamp, cm.created_at,
                        cs.session_id, s.session_type, 
                        p.name as participant_name, p.email as participant_email
                 FROM chat_messages cm
                 JOIN chat_sessions cs ON cm.chat_session_id = cs.id
                 JOIN sessions s ON cs.session_id = s.id
                 JOIN participants p ON s.participant_id = p.id
                 ORDER BY cs.id ASC, cm.timestamp ASC"
            );
            
            exportToJson($data, 'chat_logs.json');
            break;
            
        default:
            // Invalid export type
            $_SESSION['error_message'] = "Invalid export type.";
            redirect('index.php?page=admin_export');
            break;
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <h3>Export Data</h3>
        <a href="index.php?page=admin" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Export Options</h5>
                <p class="card-text">Select data to export in JSON format:</p>
                
                <div class="list-group">
                    <a href="index.php?page=admin_export&export=participants" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Export Participant List
                        <span class="badge bg-primary">JSON</span>
                    </a>
                    <a href="index.php?page=admin_export&export=pre_survey" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Export Pre-Chat Survey Responses
                        <span class="badge bg-primary">JSON</span>
                    </a>
                    <a href="index.php?page=admin_export&export=post_survey" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Export Post-Chat Survey Responses
                        <span class="badge bg-primary">JSON</span>
                    </a>
                    <a href="index.php?page=admin_export&export=chat_logs" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Export Chat Logs
                        <span class="badge bg-primary">JSON</span>
                    </a>
                </div>
                
                <div class="alert alert-info mt-4">
                    <h6 class="alert-heading">Note:</h6>
                    <p class="mb-0">Exported data will be downloaded as JSON files that can be imported into data analysis tools.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/layout/footer.php';
?> 