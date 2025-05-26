# YouTube Comments Tests

This directory contains tests for the YouTube Comments application. The tests are written using PHPUnit and cover the main functionality of the application.

## Running the Tests

To run the tests, use the following command from the project root directory:

```bash
composer test
```

Or you can run PHPUnit directly:

```bash
./vendor/bin/phpunit
```

## Test Structure

The tests are organized to mirror the structure of the source code:

- `Service/` - Tests for the service classes
  - `YouTubeClientTest.php` - Tests for the YouTubeClient service
- `Command/` - Tests for the command classes
  - `FetchCommentsCommandTest.php` - Tests for the FetchCommentsCommand
  - `DownloadCommentsCommandTest.php` - Tests for the DownloadCommentsCommand
  - `ParseCommentsCommandTest.php` - Tests for the ParseCommentsCommand

## Test Coverage

The tests cover the following functionality:

### YouTubeClient Tests

- Creating the output directory
- Downloading comments from YouTube
- Parsing comments from JSON to text
- Fetching comments (download + parse)
- Handling errors and exceptions

### Command Tests

- Executing commands with valid video IDs
- Validating video IDs
- Handling video IDs that start with dashes
- Handling missing files
- Handling errors and exceptions

## Mocking

The tests use mocking to isolate the components being tested:

- The YouTubeClient tests mock the Process class to avoid making actual HTTP requests
- The Command tests mock the YouTubeClient to avoid depending on its implementation

## Test Environment

The tests use temporary directories and files to avoid interfering with the actual application data. These are cleaned up after each test.
