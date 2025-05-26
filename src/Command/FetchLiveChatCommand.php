<?php

namespace App\Command;

use App\Service\YouTubeClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'youtube:fetch-livechat', description: 'Fetch live chat from a YouTube video and save it to a text file')]
class FetchLiveChatCommand extends Command
{
    public function __construct(private YouTubeClient $youtubeClient = new YouTubeClient)
    {
        parent::__construct('youtube:fetch-livechat');
    }

    protected function configure(): void
    {
        $this
            ->addArgument('videoId', InputArgument::REQUIRED, 'The YouTube video ID')
            ->setHelp('This command allows you to fetch live chat from a YouTube video and save it to a text file.');
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

        $io->title('YouTube Live Chat Dump');
        $io->text("Fetching live chat for video ID: $videoId");

        try {
            $outputFile = $this->youtubeClient->fetchLiveChat($videoId);
            $io->success("Live chat saved to: $outputFile");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
