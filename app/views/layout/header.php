<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 40px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
        }
        .header {
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 1px solid #e5e5e5;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            color: #777;
        }
        .chat-container {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fff;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 80%;
            position: relative;
        }
        .message-participant {
            background-color: #e5e5ea;
            align-self: flex-start;
            margin-right: auto;
        }
        .message-chaplain, .message-ai {
            background-color: #007bff;
            color: white;
            align-self: flex-end;
            margin-left: auto;
        }
        .survey-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .likert-scale {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
        }
        .likert-option {
            text-align: center;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 0.2em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin 0.75s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header text-center">
            <h1><?php echo APP_NAME; ?></h1>
        </div>
        
        <main role="main">
            <?php if (isset($pageTitle)): ?>
                <h2 class="mb-4"><?php echo $pageTitle; ?></h2>
            <?php endif; ?>
            
            <?php
            // Display flash messages if they exist
            if (isset($_SESSION['success_message'])) {
                echo displaySuccess($_SESSION['success_message']);
                unset($_SESSION['success_message']);
            }
            
            if (isset($_SESSION['error_message'])) {
                echo displayError($_SESSION['error_message']);
                unset($_SESSION['error_message']);
            }
            ?>
        </main>
    </div>
</body>
</html> 