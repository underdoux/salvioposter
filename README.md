# Blogspot Auto Poster

An automated content posting system for Blogspot using Laravel and Google's Blogger API.

## Features

- OAuth2 Google Authentication
- Automated content generation
- Draft management
- Scheduled posting to Blogspot
- Modern dashboard interface

## Requirements

- PHP >= 8.2
- Composer
- SQLite
- Google Account with Blogger API access

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

## Usage

1. Start the development server
```bash
php artisan serve
```

2. Visit `http://localhost:8000` in your browser
3. Log in with your Google account
4. Start creating and scheduling posts

## Project Structure

```
blogposter/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── GoogleAuthController.php
│   │   │   ├── BloggerController.php
│   │   │   └── PostController.php
│   │   └── Middleware/
│   │       └── EnsureOAuthTokenValid.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Post.php
│   │   └── OAuthToken.php
│   └── Services/
│       ├── BloggerService.php
│       └── ContentGeneratorService.php
└── database/
    └── migrations/
        ├── create_posts_table.php
        └── create_oauth_tokens_table.php
```

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
