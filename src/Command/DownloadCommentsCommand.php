<?php

namespace App\Command;

use App\Service\YouTubeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DownloadCommentsCommand extends Command
{
    protected static $defaultName = 'youtube:download-comments';
    protected static $defaultDescription = 'Download comments from a YouTube video and save them to a JSON file';

    private YouTubeClient $youtubeClient;

    public function __construct()
    {
        parent::__construct();
        $this->youtubeClient = new YouTubeClient();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('videoId', InputArgument::REQUIRED, 'The YouTube video ID')
            ->setHelp('This command allows you to download comments from a YouTube video and save them to a JSON file.');
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

        $io->title('YouTube Comments Download');
        $io->text("Downloading comments for video ID: $videoId");

        try {
            $outputFile = $this->youtubeClient->downloadComments($videoId);
            $io->success("Comments downloaded and saved to: $outputFile");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
