<?php

namespace App\Command;

use App\Service\YouTubeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ParseCommentsCommand extends Command
{
    protected static $defaultName = 'youtube:parse-comments';
    protected static $defaultDescription = 'Parse downloaded YouTube comments from a JSON file and save them to a text file';

    private YouTubeClient $youtubeClient;
    private string $outputDir;

    public function __construct()
    {
        parent::__construct();
        $this->youtubeClient = new YouTubeClient();
        $this->outputDir = dirname(__DIR__, 2) . '/output';
    }

    protected function configure(): void
    {
        $this
            ->addArgument('videoId', InputArgument::REQUIRED, 'The YouTube video ID')
            ->setHelp('This command allows you to parse downloaded YouTube comments from a JSON file and save them to a text file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $videoId = $input->getArgument('videoId');

        // Remove any leading -- that might have been added to escape a video ID starting with -
        if (strpos($videoId, '--') === 0) {
            $videoId = substr($videoId, 2);
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
            $io->error('Invalid YouTube video ID. It should be 11 characters long and contain only letters, numbers, underscores, and hyphens.');
            return Command::FAILURE;
        }

        $jsonFile = $this->outputDir . "/comments_{$videoId}.json";

        if (!file_exists($jsonFile)) {
            $io->error("Comments JSON file not found: $jsonFile");
            $io->note("Please download comments first using the 'youtube:download-comments' command.");
            return Command::FAILURE;
        }

        $io->title('YouTube Comments Parser');
        $io->text("Parsing comments for video ID: $videoId");

        try {
            $outputFile = $this->youtubeClient->parseComments($videoId, $jsonFile);
            $io->success("Comments parsed and saved to: $outputFile");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
