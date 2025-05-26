<?php

namespace App\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;

class YouTubeClient
{
    private string $outputDir;

    public function __construct()
    {
        $this->outputDir = dirname(__DIR__, 2) . '/output';

        // Ensure output directory exists
        if (!is_dir($this->outputDir) && !mkdir($this->outputDir, 0755, true) && !is_dir($this->outputDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->outputDir));
        }
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

        // Build the yt-dlp command
        $process = new Process([
            'yt-dlp',
            '--skip-download',
            '--write-comments',
            '--no-check-certificate',
            '--output', $tempJsonFile,
            "https://www.youtube.com/watch?v={$videoId}"
        ]);

        $process->setTimeout(300); // 5 minutes timeout

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
}
