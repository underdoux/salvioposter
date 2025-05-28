# Blogspot Auto Poster

An automated content posting system for Blogspot using Laravel and Google's Blogger API.

## Features

- OAuth2 Google Authentication
- Automated content generation with AI
- Draft management and preview
- Advanced post scheduling system:
  - Schedule posts for future publication
  - Manage scheduled posts through a dedicated interface
  - Automatic publishing via cron job
  - Retry mechanism for failed publications
  - Status tracking and notifications
- Modern, responsive dashboard interface

## Requirements

- PHP >= 8.2
- Composer
- SQLite
- Google Account with Blogger API access
- Cron job access for scheduled publishing

## Installation

1. Clone the repository
```bash
git clone <repository-url>
cd blogposter
```

2. Install dependencies
```bash
composer install
```

3. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Set up SQLite database
```bash
touch database/database.sqlite
php artisan migrate
```

5. Configure Google OAuth2
- Create a project in Google Cloud Console
- Enable Blogger API
- Create OAuth 2.0 credentials
- Add credentials to .env file:
```
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=your_callback_url
```

6. Set up the scheduler
Add the following Cron entry to your server:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```
This will run the scheduler every minute to check for and publish any due posts.

## Usage

1. Start the development server
```bash
php artisan serve
```

2. Visit `http://localhost:8000` in your browser
3. Log in with your Google account
4. Create and manage posts:
   - Write posts manually or use AI generation
   - Schedule posts for future publication
   - Preview posts before publishing
5. Manage scheduled posts:
   - View all scheduled posts
   - Edit scheduling times
   - Cancel scheduled publications
   - Monitor publication status
6. Use the dashboard to:
   - View post statistics
   - Track scheduled publications
   - Monitor failed publications

## Project Structure

```
blogposter/
├── app/
│   ├── Console/
│   │   ├── Commands/
│   │   │   └── PublishScheduledPosts.php
│   │   └── Kernel.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── GoogleAuthController.php
│   │   │   ├── BloggerController.php
│   │   │   ├── PostController.php
│   │   │   └── ScheduledPostController.php
│   │   └── Middleware/
│   │       └── EnsureOAuthTokenValid.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Post.php
│   │   ├── ScheduledPost.php
│   │   └── OAuthToken.php
│   ├── Policies/
│   │   ├── PostPolicy.php
│   │   └── ScheduledPostPolicy.php
│   └── Services/
│       ├── BloggerService.php
│       ├── ContentGeneratorService.php
│       └── SchedulingService.php
└── database/
    └── migrations/
        ├── create_posts_table.php
        ├── create_oauth_tokens_table.php
        └── create_scheduled_posts_table.php
```

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
