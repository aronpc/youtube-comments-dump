<?php

namespace AronPC\YouTubeComments\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class YouTubeClient
{
    private string $outputDir;
    private string $youtubeDlPath;
    private int $commandTimeout;
    private ?string $cookiesPath;

    public function __construct()
    {
        // Check if we're running in Laravel
        $runningInLaravel = class_exists('\Illuminate\Foundation\Application') && app() instanceof \Illuminate\Foundation\Application;

        if ($runningInLaravel) {
            // Get settings from Laravel config
            $this->outputDir = Config::get('youtube-comments.output_directory', dirname(__DIR__, 2) . '/output');
            $this->youtubeDlPath = Config::get('youtube-comments.youtube_dl_path', 'yt-dlp');
            $this->commandTimeout = Config::get('youtube-comments.command_timeout', 300);
            $this->cookiesPath = Config::get('youtube-comments.cookies_path');
        } else {
            // Use default values when running outside Laravel
            $this->outputDir = dirname(__DIR__, 2) . '/output';
            $this->youtubeDlPath = 'yt-dlp';
            $this->commandTimeout = 300;
            $this->cookiesPath = null;
        }

        // Ensure output directory exists
        if (!is_dir($this->outputDir) && !mkdir($this->outputDir, 0755, true) && !is_dir($this->outputDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->outputDir));
        }
    }

    /**
     * Fetch both comments and live chat from a YouTube video and save them to separate text files
     *
     * @param string $videoId The YouTube video ID
     * @return array The paths to the output files [comments, livechat]
     * @throws \Exception If the process fails
     */
    public function fetchCommentsAndLiveChat(string $videoId): array
    {
        $results = [];

        try {
            $results['comments'] = $this->fetchComments($videoId);
        } catch (\Exception $e) {
            // If comments fail, just log the error but continue with live chat
            $results['comments'] = null;
        }

        try {
            $results['livechat'] = $this->fetchLiveChat($videoId);
        } catch (\Exception $e) {
            // If live chat fails, just log the error
            $results['livechat'] = null;
        }

        if ($results['comments'] === null && $results['livechat'] === null) {
            throw new \Exception("Failed to fetch both comments and live chat for video ID: {$videoId}");
        }

        return $results;
    }

    /**
     * Fetch live chat from a YouTube video and save it to a text file
     *
     * @param string $videoId The YouTube video ID
     * @return string The path to the output file
     * @throws \Exception If the process fails
     */
    public function fetchLiveChat(string $videoId): string
    {
        $jsonFile = $this->downloadLiveChat($videoId);
        return $this->parseLiveChat($videoId, $jsonFile);
    }

    /**
     * Fetch comments from a YouTube video and save them to a text file
     *
     * @param string $videoId The YouTube video ID
     * @return string The path to the output file
     * @throws \Exception If the process fails
     */
    public function fetchComments(string $videoId): string
    {
        $jsonFile = $this->downloadComments($videoId);
        return $this->parseComments($videoId, $jsonFile);
    }

    /**
     * Download comments from a YouTube video and save them to a JSON file
     *
     * @param string $videoId The YouTube video ID
     * @return string The path to the JSON file containing the comments
     * @throws \Exception If the process fails
     */
    public function downloadComments(string $videoId): string
    {
        $tempJsonFile = sys_get_temp_dir() . "/{$videoId}.comments.json";

        $processArgs = [
            $this->youtubeDlPath,
            '--skip-download',
            '--write-comments',
            '--no-check-certificate',
            '--output',
            $tempJsonFile,
            "https://www.youtube.com/watch?v={$videoId}"
        ];

        if ($this->cookiesPath) {
            $cookieFile = $this->cookiesPath . '/youtube-cookies.txt';
            if (file_exists($cookieFile)) {
                $processArgs[] = '--cookies';
                $processArgs[] = $cookieFile;
            }
        }

        // Build the command using the class property
        $process = new Process($processArgs);

        // Set timeout using the class property
        $process->setTimeout($this->commandTimeout);

        try {
            $process->run();

            // Check if the process was successful
            if (!$process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();

                // Check for specific error messages
                if (preg_match('/This live event will begin in (\d+) hours?/', $errorOutput, $matches)) {
                    throw new \Exception("This video is a future live event that will begin in {$matches[1]} hours. Comments are not available yet.");
                } elseif (preg_match('/This live event will begin in (\d+) minutes?/', $errorOutput, $matches)) {
                    throw new \Exception("This video is a future live event that will begin in {$matches[1]} minutes. Comments are not available yet.");
                } elseif (strpos($errorOutput, 'This live event will begin') !== false) {
                    throw new \Exception("This video is a future live event. Comments are not available yet.");
                } elseif (strpos($errorOutput, 'comments are disabled') !== false) {
                    throw new \Exception("Comments are disabled for this video.");
                } else {
                    throw new \Exception("Failed to execute yt-dlp: " . $process->getExitCodeText() . "\n\nError Output: " . $errorOutput);
                }
            }

            // Check if the comments file was created
            $commentsJsonFile = $tempJsonFile . '.info.json';
            if (!file_exists($commentsJsonFile)) {
                throw new \Exception("Failed to download comments. The video might not have any comments or they might be disabled.");
            }

            // Save the JSON file to the output directory for persistence
            $outputJsonFile = $this->outputDir . "/comments_{$videoId}.json";
            copy($commentsJsonFile, $outputJsonFile);

            // Return the path to the JSON file
            return $outputJsonFile;
        } catch (ProcessFailedException $exception) {
            throw new \Exception("Failed to execute yt-dlp: " . $exception->getMessage());
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Parse a JSON file containing comments and save them to a text file
     *
     * @param string $videoId The YouTube video ID
     * @param string $jsonFile The path to the JSON file containing the comments
     * @return string The path to the output text file
     * @throws \Exception If the process fails
     */
    public function parseComments(string $videoId, string $jsonFile): string
    {
        $outputFile = $this->outputDir . "/comments_{$videoId}.txt";

        try {
            // Parse the JSON file
            $commentsData = json_decode(file_get_contents($jsonFile), true);

            if (!isset($commentsData['comments'])) {
                throw new \Exception("No comments found in the downloaded data.");
            }

            // Format and save the comments
            $this->formatAndSaveComments($commentsData['comments'], $outputFile);

            return $outputFile;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Format and save comments to a text file
     *
     * @param array $comments The comments array
     * @param string $outputFile The path to the output file
     * @return void
     */
    private function formatAndSaveComments(array $comments, string $outputFile): void
    {
        $formattedComments = '';

        foreach ($comments as $comment) {
            $author = $comment['author'] ?? 'Anonymous';
            $text = $comment['text'] ?? 'No comment text';

            $formattedComments .= "{$author}:\n{$text}\n\n";
        }

        file_put_contents($outputFile, $formattedComments);
    }

    /**
     * Download live chat from a YouTube video and save it to a JSON file
     *
     * @param string $videoId The YouTube video ID
     * @return string The path to the JSON file containing the live chat
     * @throws \Exception If the process fails
     */
    public function downloadLiveChat(string $videoId): string
    {
        $tempJsonFile = sys_get_temp_dir() . "/{$videoId}.livechat.json";

        $processArgs = [
            $this->youtubeDlPath,
            '--skip-download',
            '--write-subs',
            '--sub-langs', 'live_chat',
            '--no-check-certificate',
            '--output', $tempJsonFile,
            "https://www.youtube.com/watch?v={$videoId}"
        ];

        if ($this->cookiesPath) {
            $cookieFile = $this->cookiesPath . '/youtube-cookies.txt';
            if (file_exists($cookieFile)) {
                $processArgs[] = '--cookies';
                $processArgs[] = $cookieFile;
            }
        }

        // Build the command using the class property
        $process = new Process($processArgs);

        // Set timeout using the class property
        $process->setTimeout($this->commandTimeout);

        try {
            $process->run();

            // Check if the process was successful
            if (!$process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();

                // Check for specific error messages
                if (strpos($errorOutput, 'no live chat') !== false) {
                    throw new \Exception("No live chat available for this video.");
                } else {
                    throw new \Exception("Failed to download live chat: " . $process->getExitCodeText() . "\n\nError Output: " . $errorOutput);
                }
            }

            // Check if the live chat file was created
            $liveChatJsonFile = $tempJsonFile . '.live_chat.json';
            if (!file_exists($liveChatJsonFile)) {
                throw new \Exception("Failed to download live chat. The video might not have a live chat replay available.");
            }

            // Save the JSON file to the output directory for persistence
            $outputJsonFile = $this->outputDir . "/livechat_{$videoId}.json";
            copy($liveChatJsonFile, $outputJsonFile);

            // Return the path to the JSON file
            return $outputJsonFile;
        } catch (ProcessFailedException $exception) {
            throw new \Exception("Failed to execute yt-dlp for live chat: " . $exception->getMessage());
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Parse a JSON file containing live chat and save it to a text file
     *
     * @param string $videoId The YouTube video ID
     * @param string $jsonFile The path to the JSON file containing the live chat
     * @return string The path to the output text file
     * @throws \Exception If the process fails
     */
    public function parseLiveChat(string $videoId, string $jsonFile): string
    {
        $outputFile = $this->outputDir . "/livechat_{$videoId}.txt";

        try {
            // Read the file content
            $fileContent = file_get_contents($jsonFile);

            // Each line is a separate JSON object
            $lines = explode("\n", $fileContent);

            // Extract chat messages from each line
            $chatMessages = [];
            foreach ($lines as $line) {
                if (empty(trim($line))) {
                    continue;
                }

                $lineData = json_decode($line, true);
                if (!$lineData) {
                    continue;
                }

                // Extract chat messages from the replayChatItemAction structure
                if (isset($lineData['replayChatItemAction']['actions'])) {
                    foreach ($lineData['replayChatItemAction']['actions'] as $action) {
                        if (isset($action['addChatItemAction']['item']['liveChatTextMessageRenderer'])) {
                            $renderer = $action['addChatItemAction']['item']['liveChatTextMessageRenderer'];

                            // Extract author name
                            $author = $renderer['authorName']['simpleText'] ?? 'Anonymous';

                            // Extract message text
                            $text = '';
                            if (isset($renderer['message']['runs'])) {
                                foreach ($renderer['message']['runs'] as $run) {
                                    if (isset($run['text'])) {
                                        $text .= $run['text'];
                                    } elseif (isset($run['emoji']['emojiId'])) {
                                        $text .= $run['emoji']['emojiId'];
                                    }
                                }
                            }

                            // Extract timestamp
                            $timestamp = $renderer['timestampText']['simpleText'] ?? '';

                            // Add to chat messages array
                            $chatMessages[] = [
                                'author' => $author,
                                'text' => $text,
                                'timestamp' => $timestamp
                            ];
                        }
                    }
                }
            }

            if (empty($chatMessages)) {
                throw new \Exception("No live chat messages found in the downloaded data.");
            }

            // Format and save the live chat messages
            $this->formatAndSaveLiveChat($chatMessages, $outputFile);

            return $outputFile;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Format and save live chat messages to a text file
     *
     * @param array $chatMessages The live chat messages array
     * @param string $outputFile The path to the output file
     * @return void
     */
    private function formatAndSaveLiveChat(array $chatMessages, string $outputFile): void
    {
        $formattedChat = '';

        foreach ($chatMessages as $message) {
            $author = $message['author'] ?? 'Anonymous';
            $text = $message['text'] ?? 'No message text';
            $timestamp = $message['timestamp'] ?? '';

            if ($timestamp) {
                $formattedChat .= "[{$timestamp}] ";
            }

            $formattedChat .= "{$author}: {$text}\n";
        }

        file_put_contents($outputFile, $formattedChat);
    }
}
