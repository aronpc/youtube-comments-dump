<?php

namespace App\Tests\Command;

use App\Command\ParseCommentsCommand;
use App\Service\YouTubeClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ParseCommentsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private YouTubeClient $youtubeClient;
    private string $testOutputDir;
    private string $testVideoId = 'dQw4w9WgXcQ';

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock output directory for testing
        $this->testOutputDir = sys_get_temp_dir() . '/youtube-comments-test-' . uniqid('', true);
        mkdir($this->testOutputDir, 0755, true);

        // Create a mock YouTubeClient
        $this->youtubeClient = $this->createMock(YouTubeClient::class);

        // Create a new ParseCommentsCommand
        $command = new ParseCommentsCommand();

        // Use reflection to replace the YouTubeClient with our mock
        $reflectionClass = new \ReflectionClass(ParseCommentsCommand::class);
        $youtubeClientProperty = $reflectionClass->getProperty('youtubeClient');
        $youtubeClientProperty->setAccessible(true);
        $youtubeClientProperty->setValue($command, $this->youtubeClient);

        // Use reflection to replace the outputDir with our test directory
        $outputDirProperty = $reflectionClass->getProperty('outputDir');
        $outputDirProperty->setAccessible(true);
        $outputDirProperty->setValue($command, $this->testOutputDir);

        // Create a new Application and add the command
        $application = new Application();
        $application->add($command);

        // Create a CommandTester
        $this->commandTester = new CommandTester($application->find('youtube:parse-comments'));
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->removeDirectory($this->testOutputDir);

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    /**
     * @test
     */
    public function testExecuteWithValidVideoIdAndExistingJsonFile(): void
    {
        // Create a test JSON file
        $jsonFile = $this->testOutputDir . "/comments_{$this->testVideoId}.json";
        file_put_contents($jsonFile, json_encode(['comments' => []]));

        // Configure the mock YouTubeClient
        $outputFile = $this->testOutputDir . "/comments_{$this->testVideoId}.txt";

        $this->youtubeClient->expects($this->once())
            ->method('parseComments')
            ->with($this->testVideoId, $jsonFile)
            ->willReturn($outputFile);

        // Execute the command
        $this->commandTester->execute([
            'videoId' => $this->testVideoId,
        ]);

        // Assert that the command output contains the success message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Comments parsed and saved to:', $output);

        // Check for parts of the path instead of the full path, as the output might format it with line breaks
        $this->assertStringContainsString('comments_' . $this->testVideoId, $output);

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
    public function testExecuteWithMissingJsonFile(): void
    {
        // Execute the command with a valid video ID but no JSON file
        $this->commandTester->execute([
            'videoId' => $this->testVideoId,
        ]);

        // Assert that the command output contains the error message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Comments JSON file not found', $output);
        $this->assertStringContainsString("Please download comments first", $output);

        // Assert that the command returned failure
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    /**
     * @test
     */
    public function testExecuteWithVideoIdStartingWithDash(): void
    {
        // Create a test JSON file
        $videoId = '-dQw9WgXcQ';
        $jsonFile = $this->testOutputDir . "/comments_{$videoId}.json";
        file_put_contents($jsonFile, json_encode(['comments' => []]));

        // Configure the mock YouTubeClient
        $outputFile = $this->testOutputDir . "/comments_{$videoId}.txt";

        $this->youtubeClient->expects($this->once())
            ->method('parseComments')
            ->with($videoId, $jsonFile)
            ->willReturn($outputFile);

        // Execute the command with a video ID that starts with a dash
        // Note: We're not actually prepending -- to the video ID, as the command
        // expects the actual video ID without the -- prefix
        $this->commandTester->execute([
            'videoId' => $videoId,
        ]);

        // Assert that the command output contains the success message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Comments parsed and saved to:', $output);

        // Check for parts of the path instead of the full path, as the output might format it with line breaks
        $this->assertStringContainsString('comments_' . $videoId, $output);

        // Assert that the command returned success
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * @test
     */
    public function testExecuteWhenParseCommentsFails(): void
    {
        // Create a test JSON file
        $jsonFile = $this->testOutputDir . "/comments_{$this->testVideoId}.json";
        file_put_contents($jsonFile, json_encode(['comments' => []]));

        // Configure the mock YouTubeClient to throw an exception
        $errorMessage = 'Failed to parse comments.';

        $this->youtubeClient->expects($this->once())
            ->method('parseComments')
            ->with($this->testVideoId, $jsonFile)
            ->willThrowException(new \Exception($errorMessage));

        // Execute the command
        $this->commandTester->execute([
            'videoId' => $this->testVideoId,
        ]);

        // Assert that the command output contains the error message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Error:', $output);
        $this->assertStringContainsString($errorMessage, $output);

        // Assert that the command returned failure
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }
}
