# Human vs. AI Chat Experiment

This web application is designed for conducting research comparing human-to-human and AI-to-human chat conversations in a pastoral care context. Participants are randomly assigned to either a human chaplain or an AI system, and complete surveys before and after the chat interaction.

## Features

- Randomized assignment to human chaplain or AI system
- Pre-chat and post-chat surveys with customizable questions
- Real-time chat interface
- Human chaplain interface via Twilio SMS API
- AI chat powered by OpenAI API
- Admin dashboard for managing the experiment
- Export functionality for research data

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- OpenAI API key
- Twilio account with SMS capabilities

## Installation

1. Clone this repository to your web server:

```
git clone <repository-url>
```

2. Create a MySQL database for the application.

3. Import the database schema from `app/database/schema.sql`:

```
mysql -u your_username -p your_database_name < app/database/schema.sql
```

4. Update the configuration in `app/config/config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

// API Keys
define('OPENAI_API_KEY', 'your_openai_api_key');
define('TWILIO_ACCOUNT_SID', 'your_twilio_account_sid');
define('TWILIO_AUTH_TOKEN', 'your_twilio_auth_token');
define('TWILIO_PHONE_NUMBER', 'your_twilio_phone_number');

// Application settings (update as needed)
define('BASE_URL', 'http://your-domain.com/app-path');
define('ADMIN_EMAIL', 'your@email.com');
```

5. For local development, you can use PHP's built-in server:

```
php -S localhost:8000
```

## Usage

### Participant Flow

1. **Welcome Page**: Participants see an introduction to the research.
2. **Consent Form**: Participants provide consent and enter their name and email.
3. **Pre-Chat Survey**: Participants complete a survey before the chat.
4. **Chat Session**: Participants are randomly assigned to either:
   - Human Chaplain: Messages are relayed via SMS to a chaplain
   - AI Chatbot: OpenAI API handles responses
5. **Post-Chat Survey**: Participants complete a final survey.
6. **Thank You Page**: Participants receive confirmation of completion.

### Admin Interface

Access the admin interface at `/index.php?page=admin_login` with the default credentials:
- Username: `admin`
- Password: `admin123`

From the admin dashboard, you can:
- View participant statistics
- Manage chaplain information
- Edit survey questions
- Modify the consent policy
- Export data for analysis

## Development Notes

### Testing

- For local testing, debugging is enabled by default.
- Sample survey questions are automatically generated if none exist.
- When no chaplains are available in development mode, the system will fall back to AI.

### Security

- Default admin credentials should be changed before deployment.
- API keys should be protected and not committed to version control.
- Production deployments should have `DEBUG` set to `false`.

## License

This project is for research purposes. All rights reserved.

## Contact

For questions, email Tay Moss 