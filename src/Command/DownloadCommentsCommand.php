<?php

namespace AronPC\YouTubeComments\Command;

use AronPC\YouTubeComments\Service\YouTubeClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'youtube:download-comments', description: 'Download comments from a YouTube video and save them to a JSON file')]
class DownloadCommentsCommand extends Command
{
    public function __construct(private YouTubeClient $youtubeClient = new YouTubeClient)
    {
        parent::__construct('youtube:download-comments');
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

        // For video IDs starting with a dash, we need to handle them specially
        // because the dash is part of the ID but might be causing issues with length validation
        $effectiveLength = strlen($videoId);
        if ($videoId[0] === '-') {
            // If the ID starts with a dash, we'll consider it as having an extra character
            // for validation purposes
            $effectiveLength = strlen($videoId) + 1;
        }

        // Check if the video ID is effectively 11 characters long
        if ($effectiveLength !== 11) {
            $io->error('Invalid YouTube video ID. It should be 11 characters long.');
            return Command::FAILURE;
        }

        // Check if the video ID contains only allowed characters
        if (!ctype_alnum(str_replace(['-', '_'], '', $videoId))) {
            $io->error('Invalid YouTube video ID. It should contain only letters, numbers, underscores, and hyphens.');
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
