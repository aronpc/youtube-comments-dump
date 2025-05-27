# YouTube Comments for Laravel

A Laravel package to download YouTube comments without using API keys, utilizing yt-dlp as the backend for extraction.

## Requirements

- PHP 8.3 or higher
- Laravel 10.x or higher
- yt-dlp installed and accessible via terminal

## Installation

1. Install the package via Composer:
   ```bash
   composer require aronpc/youtube-comments
   ```

2. The package will be automatically discovered by Laravel.

3. Publish the configuration file:
   ```bash
   php artisan vendor:publish --provider="AronPC\YouTubeComments\YouTubeCommentsServiceProvider" --tag="config"
   ```

4. Install yt-dlp (if not already installed):
   ```bash
   # Linux/macOS
   sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
   sudo chmod a+rx /usr/local/bin/yt-dlp

   # Windows
   # Download from https://github.com/yt-dlp/yt-dlp/releases and add to your PATH
   ```

5. Configure the package in `config/youtube-comments.php` if needed.

## Usage

### Using Artisan Commands

This package provides several Artisan commands to interact with YouTube comments and live chat.

#### Fetch Comments (All-in-one)

To fetch comments from a YouTube video in a single step (download and parse), use the following command:

```bash
php artisan youtube:fetch-comments VIDEO_ID
```

Replace `VIDEO_ID` with the ID of the YouTube video. For example:

```bash
php artisan youtube:fetch-comments dQw4w9WgXcQ
```

This will generate a file in the configured output directory named `comments_VIDEO_ID.txt` containing all the comments from the video.

#### Fetch Live Chat

To fetch live chat from a YouTube video that had a live stream, use:

```bash
php artisan youtube:fetch-livechat VIDEO_ID
```

This will generate a file in the configured output directory named `livechat_VIDEO_ID.txt` containing all the live chat messages from the video.

#### Fetch Both Comments and Live Chat

To fetch both comments and live chat in a single operation, use:

```bash
php artisan youtube:fetch-all VIDEO_ID
```

This will generate two files in the configured output directory:
- `comments_VIDEO_ID.txt` containing all the comments
- `livechat_VIDEO_ID.txt` containing all the live chat messages

If either comments or live chat are not available for the video, the command will still succeed but will display a warning message.

#### Download Comments Only

If you want to only download the comments and save them as a JSON file (useful for debugging), use:

```bash
php artisan youtube:download-comments VIDEO_ID
```

This will generate a file in the configured output directory named `comments_VIDEO_ID.json` containing the raw JSON data.

#### Parse Comments Only

To parse previously downloaded comments from a JSON file, use:

```bash
php artisan youtube:parse-comments VIDEO_ID
```

This command expects a file named `comments_VIDEO_ID.json` to exist in the configured output directory.

### Using the Facade

You can also use the provided facade to interact with the YouTube client directly in your code:

```php
use AronPC\YouTubeComments\Facades\YouTubeComments;

// Fetch comments from a YouTube video
$commentsFile = YouTubeComments::fetchComments('dQw4w9WgXcQ');

// Fetch live chat from a YouTube video
$liveChatFile = YouTubeComments::fetchLiveChat('dQw4w9WgXcQ');

// Fetch both comments and live chat
$results = YouTubeComments::fetchCommentsAndLiveChat('dQw4w9WgXcQ');
$commentsFile = $results['comments'];
$liveChatFile = $results['livechat'];

// Download comments only (returns path to JSON file)
$jsonFile = YouTubeComments::downloadComments('dQw4w9WgXcQ');

// Parse comments from a JSON file
$outputFile = YouTubeComments::parseComments('dQw4w9WgXcQ', $jsonFile);
```

### Output Format

The parsed comments are saved in a text file with the following format:

```
Author:
Comment text

Author:
Comment text
```

## How It Works

1. The application uses yt-dlp to download the comments from the YouTube video
2. It processes the JSON data and formats the comments
3. The formatted comments are saved to a text file in the output directory

## Configuration

The package configuration file is located at `config/youtube-comments.php` after publishing. It includes the following options:

```php
return [
    // Directory where downloaded comments and live chat messages will be saved
    'output_directory' => env('YOUTUBE_COMMENTS_OUTPUT_DIR', storage_path('app/youtube-comments')),

    // Path to the youtube-dl or yt-dlp executable
    'youtube_dl_path' => env('YOUTUBE_DL_PATH', 'yt-dlp'),

    // Maximum time (in seconds) to wait for a command to complete
    'command_timeout' => env('YOUTUBE_COMMENTS_TIMEOUT', 300),
];
```

You can customize these settings by editing the configuration file or by setting the corresponding environment variables in your `.env` file.

## Testing

The package includes a comprehensive test suite built with PHPUnit. The tests cover the main functionality of the package, including:

- Service classes (YouTubeClient)
- Command classes (FetchCommentsCommand, DownloadCommentsCommand, ParseCommentsCommand)

### Running Tests

If you want to run the tests for this package, you can clone the repository and run:

```bash
composer install
composer test
```

## Troubleshooting

- Make sure yt-dlp is installed and accessible from the command line
- Some videos may have comments disabled, in which case no comments will be downloaded
- Future live events that haven't started yet don't have comments available
- If you encounter any issues, try updating yt-dlp to the latest version
- For video IDs that start with a hyphen (e.g., -6oKXN8D6BI), you need to use the `--` separator to prevent the shell from interpreting the ID as a command option:
  ```bash
  php artisan youtube:fetch-comments -- -6oKXN8D6BI
  ```
  Note: The package has special handling for video IDs that start with a hyphen to ensure they are processed correctly.

## License

This package is open-sourced software licensed under the MIT license.
