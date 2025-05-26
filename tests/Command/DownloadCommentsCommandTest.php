<?php

namespace App\Tests\Command;

use App\Command\DownloadCommentsCommand;
use App\Service\YouTubeClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DownloadCommentsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private YouTubeClient $youtubeClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock YouTubeClient
        $this->youtubeClient = $this->createMock(YouTubeClient::class);

        // Create a new DownloadCommentsCommand with our mock
        $command = new DownloadCommentsCommand($this->youtubeClient);

        // Create a new Application and add the command
        $application = new Application();
        $application->add($command);

        // Create a CommandTester
        $this->commandTester = new CommandTester($application->find('youtube:download-comments'));
    }

    /**
     * @test
     */
    public function testExecuteWithValidVideoId(): void
    {
        // Configure the mock YouTubeClient
        $videoId = 'dQw4w9WgXcQ';
        $outputFile = '/path/to/output/file.json';

        $this->youtubeClient->expects($this->once())
            ->method('downloadComments')
            ->with($videoId)
            ->willReturn($outputFile);

        // Execute the command
        $this->commandTester->execute([
            'videoId' => $videoId,
        ]);

        // Assert that the command output contains the success message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Comments downloaded and saved to:', $output);
        $this->assertStringContainsString($outputFile, $output);

        // Assert that the command returned success
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * @test
     */
    public function testExecuteWithInvalidVideoId(): void
    {
        // Execute the command with an invalid video ID
        $this->commandTester->execute([
            'videoId' => 'invalid',
        ]);

        // Assert that the command output contains the error message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Invalid YouTube video ID', $output);

        // Assert that the command returned failure
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    /**
     * @test
     */
    public function testExecuteWithVideoIdStartingWithDash(): void
    {
        // Configure the mock YouTubeClient
        $videoId = '-dQw9WgXcQ';
        $outputFile = '/path/to/output/file.json';

        $this->youtubeClient->expects($this->once())
            ->method('downloadComments')
            ->with($videoId)
            ->willReturn($outputFile);

        // Execute the command with a video ID that starts with a dash
        // Note: We're not actually prepending -- to the video ID, as the command
        // expects the actual video ID without the -- prefix
        $this->commandTester->execute([
            'videoId' => $videoId,
        ]);

        // Assert that the command output contains the success message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Comments downloaded and saved to:', $output);

        // Check for parts of the path instead of the full path, as the output might format it with line breaks
        $pathParts = explode('/', $outputFile);
        $filename = end($pathParts);
        $this->assertStringContainsString($filename, $output);

        // Assert that the command returned success
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * @test
     */
    public function testExecuteWhenDownloadCommentsFails(): void
    {
        // Configure the mock YouTubeClient to throw an exception
        $videoId = 'dQw4w9WgXcQ';
        $errorMessage = 'Comments are disabled for this video.';

        $this->youtubeClient->expects($this->once())
            ->method('downloadComments')
            ->with($videoId)
            ->willThrowException(new \Exception($errorMessage));

        // Execute the command
        $this->commandTester->execute([
            'videoId' => $videoId,
        ]);

        // Assert that the command output contains the error message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Error:', $output);
        $this->assertStringContainsString($errorMessage, $output);

        // Assert that the command returned failure
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }
}
