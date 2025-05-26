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

To fetch comments from a YouTube video, use the following command:

```bash
php bin/console youtube:fetch-comments VIDEO_ID
```

Replace `VIDEO_ID` with the ID of the YouTube video. For example:

```bash
php bin/console youtube:fetch-comments dQw4w9WgXcQ
```

This will generate a file in the `output` directory named `comments_VIDEO_ID.txt` containing all the comments from the video in the following format:

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

## Troubleshooting

- Make sure yt-dlp is installed and accessible from the command line
- Some videos may have comments disabled, in which case no comments will be downloaded
- Future live events that haven't started yet don't have comments available
- If you encounter any issues, try updating yt-dlp to the latest version
