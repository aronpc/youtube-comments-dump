# YouTube Comments Dump

A PHP application to download YouTube comments without using API keys, utilizing yt-dlp as the backend for extraction.

## Requirements

- PHP 8.0 or higher
- Composer
- yt-dlp installed and accessible via terminal

## Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/yourusername/youtube-comments-dump.git
   cd youtube-comments-dump
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install yt-dlp (if not already installed):
   ```bash
   # Linux/macOS
   sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
   sudo chmod a+rx /usr/local/bin/yt-dlp

   # Windows
   # Download from https://github.com/yt-dlp/yt-dlp/releases and add to your PATH
   ```

## Usage

### Fetch Comments (All-in-one)

To fetch comments from a YouTube video in a single step (download and parse), use the following command:

```bash
php bin/console youtube:fetch-comments VIDEO_ID
```

Replace `VIDEO_ID` with the ID of the YouTube video. For example:

```bash
php bin/console youtube:fetch-comments dQw4w9WgXcQ
```

This will generate a file in the `output` directory named `comments_VIDEO_ID.txt` containing all the comments from the video.

### Fetch Live Chat

To fetch live chat from a YouTube video that had a live stream, use:

```bash
php bin/console youtube:fetch-livechat VIDEO_ID
```

This will generate a file in the `output` directory named `livechat_VIDEO_ID.txt` containing all the live chat messages from the video.

### Fetch Both Comments and Live Chat

To fetch both comments and live chat in a single operation, use:

```bash
php bin/console youtube:fetch-all VIDEO_ID
```

This will generate two files in the `output` directory:
- `comments_VIDEO_ID.txt` containing all the comments
- `livechat_VIDEO_ID.txt` containing all the live chat messages

If either comments or live chat are not available for the video, the command will still succeed but will display a warning message.

### Download Comments Only

If you want to only download the comments and save them as a JSON file (useful for debugging), use:

```bash
php bin/console youtube:download-comments VIDEO_ID
```

This will generate a file in the `output` directory named `comments_VIDEO_ID.json` containing the raw JSON data.

### Parse Comments Only

To parse previously downloaded comments from a JSON file, use:

```bash
php bin/console youtube:parse-comments VIDEO_ID
```

This command expects a file named `comments_VIDEO_ID.json` to exist in the `output` directory.

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

## Testing

The application includes a comprehensive test suite built with PHPUnit. The tests cover the main functionality of the application, including:

- Service classes (YouTubeClient)
- Command classes (FetchCommentsCommand, DownloadCommentsCommand, ParseCommentsCommand)

### Running Tests

To run the tests, use the following command:

```bash
composer test
```

Or you can run PHPUnit directly:

```bash
./vendor/bin/phpunit
```

For more information about the tests, see the [tests/README.md](tests/README.md) file.

## Troubleshooting

- Make sure yt-dlp is installed and accessible from the command line
- Some videos may have comments disabled, in which case no comments will be downloaded
- Future live events that haven't started yet don't have comments available
- If you encounter any issues, try updating yt-dlp to the latest version
- For video IDs that start with a hyphen (e.g., -6oKXN8D6BI), you need to use the `--` separator to prevent the shell from interpreting the ID as a command option:
  ```bash
  php bin/console youtube:fetch-comments -- -6oKXN8D6BI
  ```
  Note: The application has special handling for video IDs that start with a hyphen to ensure they are processed correctly.
