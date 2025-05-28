# Contributing to Blogspot Auto Poster

First off, thank you for considering contributing to Blogspot Auto Poster! It's people like you that make this project such a great tool.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

* Use a clear and descriptive title
* Describe the exact steps which reproduce the problem
* Provide specific examples to demonstrate the steps
* Describe the behavior you observed after following the steps
* Explain which behavior you expected to see instead and why
* Include screenshots if possible

### Suggesting Enhancements

If you have a suggestion for the project, we'd love to hear it. Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

* A clear and descriptive title
* A detailed description of the proposed enhancement
* An explanation of why this enhancement would be useful
* Examples of how it would be used

### Pull Requests

* Fill in the required template
* Do not include issue numbers in the PR title
* Follow the PHP coding style (PSR-12)
* Include thoughtfully-worded, well-structured tests
* Document new code
* End all files with a newline

## Development Process

1. Fork the repository
2. Create a new branch for your feature
3. Make your changes
4. Write or adapt tests as needed
5. Update documentation as needed
6. Submit a pull request

### Setting up your development environment

```bash
# Clone your fork
git clone git@github.com:YOUR_USERNAME/blogposter.git

# Add the main repository as a remote
git remote add upstream git@github.com:ORIGINAL_OWNER/blogposter.git

# Install dependencies
composer install

# Set up your environment
cp .env.example .env
php artisan key:generate

# Create database
touch database/database.sqlite

# Run migrations
php artisan migrate
```

### Coding Standards

* Follow PSR-12 coding standards
* Use meaningful variable and function names
* Comment your code when necessary
* Keep functions focused and concise
* Write tests for new functionality

### Commit Messages

* Use the present tense ("Add feature" not "Added feature")
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
* Limit the first line to 72 characters or less
* Reference issues and pull requests liberally after the first line

## Additional Notes

### Issue and Pull Request Labels

* `bug` - Something isn't working
* `enhancement` - New feature or request
* `documentation` - Improvements or additions to documentation
* `help-wanted` - Extra attention is needed
* `good-first-issue` - Good for newcomers

## Questions?

Feel free to open an issue with your question or contact the maintainers directly.

Thank you for contributing to Blogspot Auto Poster!
