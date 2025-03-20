<?php
$pageTitle = "Admin Dashboard";
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

// Get statistics
$totalParticipants = $db->fetchOne("SELECT COUNT(*) as count FROM participants")['count'];
$totalSessions = $db->fetchOne("SELECT COUNT(*) as count FROM sessions")['count'];
$humanSessions = $db->fetchOne("SELECT COUNT(*) as count FROM sessions WHERE session_type = 'human'")['count'];
$aiSessions = $db->fetchOne("SELECT COUNT(*) as count FROM sessions WHERE session_type = 'ai'")['count'];
$completedSessions = $db->fetchOne("SELECT COUNT(*) as count FROM sessions WHERE status = 'post_survey_completed'")['count'];
$activeChaplains = $db->fetchOne("SELECT COUNT(*) as count FROM chaplains WHERE status = 'active'")['count'];

// Recent participants
$recentParticipants = $db->fetchAll(
    "SELECT p.id, p.name, p.email, p.created_at, s.session_type, s.status 
     FROM participants p 
     JOIN sessions s ON p.id = s.participant_id 
     ORDER BY p.created_at DESC LIMIT 5"
);

// Get API connection status
require_once 'app/utils/openai_api.php';
require_once 'app/utils/twilio_api.php';

$openaiStatus = false;
$twilioStatus = false;

if (DEBUG) {
    try {
        $openai = new OpenAIAPI();
        $openaiStatus = OPENAI_API_KEY !== 'your_openai_api_key_here' && $openai->testConnection();
    } catch (Exception $e) {
        logError("OpenAI API connection test error: " . $e->getMessage());
    }
    
    try {
        $twilio = new TwilioAPI();
        $twilioStatus = TWILIO_ACCOUNT_SID !== 'your_twilio_account_sid_here' && $twilio->testConnection();
    } catch (Exception $e) {
        logError("Twilio API connection test error: " . $e->getMessage());
    }
}

// Admin menu
$adminMenu = [
    [
        'title' => 'Chaplains',
        'url' => 'index.php?page=admin_chaplains',
        'description' => 'Manage human chaplains on standby list'
    ],
    [
        'title' => 'Participants',
        'url' => 'index.php?page=admin_participants',
        'description' => 'View participant data and session details'
    ],
    [
        'title' => 'Surveys',
        'url' => 'index.php?page=admin_surveys',
        'description' => 'Edit pre-chat and post-chat survey questions'
    ],
    [
        'title' => 'Consent Policy',
        'url' => 'index.php?page=admin_consent',
        'description' => 'Edit the consent policy text'
    ],
    [
        'title' => 'Export Data',
        'url' => 'index.php?page=admin_export',
        'description' => 'Export participant list, survey responses, and chat logs'
    ]
];
?>

<div class="row mb-4">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <h3>Admin Dashboard</h3>
        <div>
            <span class="me-2">Welcome, <?php echo $_SESSION['admin_username']; ?></span>
            <a href="index.php?page=admin_logout" class="btn btn-outline-secondary btn-sm">Logout</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Research Statistics</h5>
                <div class="row">
                    <div class="col-md-2">
                        <div class="stat-card text-center p-2">
                            <h3><?php echo $totalParticipants; ?></h3>
                            <p class="mb-0">Total Participants</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card text-center p-2">
                            <h3><?php echo $totalSessions; ?></h3>
                            <p class="mb-0">Total Sessions</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card text-center p-2">
                            <h3><?php echo $humanSessions; ?></h3>
                            <p class="mb-0">Human Sessions</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card text-center p-2">
                            <h3><?php echo $aiSessions; ?></h3>
                            <p class="mb-0">AI Sessions</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card text-center p-2">
                            <h3><?php echo $completedSessions; ?></h3>
                            <p class="mb-0">Completed</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card text-center p-2">
                            <h3><?php echo $activeChaplains; ?></h3>
                            <p class="mb-0">Active Chaplains</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Recent Participants</h5>
                <?php if (empty($recentParticipants)): ?>
                    <p class="text-muted">No participants yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentParticipants as $participant): ?>
                                    <tr>
                                        <td><?php echo $participant['name']; ?></td>
                                        <td><?php echo $participant['email']; ?></td>
                                        <td><?php echo ucfirst($participant['session_type']); ?></td>
                                        <td><?php echo str_replace('_', ' ', ucfirst($participant['status'])); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($participant['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <a href="index.php?page=admin_participants" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">API Status</h5>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        OpenAI API
                        <?php if ($openaiStatus): ?>
                            <span class="badge bg-success">Connected</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Not Connected</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Twilio SMS API
                        <?php if ($twilioStatus): ?>
                            <span class="badge bg-success">Connected</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Not Connected</span>
                        <?php endif; ?>
                    </li>
                </ul>
                
                <?php if (!$openaiStatus || !$twilioStatus): ?>
                    <div class="alert alert-warning small mb-0">
                        <p class="mb-1"><strong>Note:</strong> API keys need to be configured in the config file.</p>
                        <p class="mb-0">Edit <code>app/config/config.php</code> to set your API keys.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Admin Menu</h5>
                <div class="row">
                    <?php foreach ($adminMenu as $menuItem): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $menuItem['title']; ?></h5>
                                    <p class="card-text"><?php echo $menuItem['description']; ?></p>
                                    <a href="<?php echo $menuItem['url']; ?>" class="btn btn-sm btn-primary">Go to <?php echo $menuItem['title']; ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/layout/footer.php';
?> 