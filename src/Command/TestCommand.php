<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:test',
    description: 'test command',
)]
class TestCommand extends Command
{
    private LoggerInterface $logger;
    public function __construct(
        #[Target('app_customLogger')]
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure(): void
    {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->logger->info('Test command - Début');
        $processStartTime = hrtime(true);


        $id = str_replace('.', '_', uniqid('client_', true));
        $gtfsId = sprintf('%s.zip', $id);

        //
        //   COPIE
        //
        $io->writeln('Copie du gtfs');
        $copyStartTime = hrtime(true);
        $process = new Process(['cp', './data/gtfs/gtfs-aix.zip', sprintf('./data/gtfs/%s', $gtfsId)]);
        $process->run();
        if (!$process->isSuccessful()) {
            $io->error('Impossible de copier le gtfs');
            $this->logger->error('Impossible de copier le gtfs - ' . $process->getErrorOutput());
            return Command::FAILURE;
        }
        $copyEndTime = hrtime(true);
        $copyTime = ($copyEndTime - $copyStartTime) / 1e+9;
        $io->writeln(sprintf('Copie du gtfs terminée en %.03f secondes', $copyTime));

        //
        //   PFAEDLE
        //
        $io->writeln(sprintf('Lancement du Pfaedle sur le gtfs %s', $gtfsId));
        $pfaedleStartTime = hrtime(true);
        $hostProjectDir = $_ENV['HOST_PROJECT_DIR'] ?? getcwd();
        $process = new Process([
            'docker','run','-i','--rm',
            '--platform','linux/amd64',
            '--volume', sprintf('%s/data/osm:/osm', $hostProjectDir),
            '--volume', sprintf('%s/data/gtfs:/gtfs', $hostProjectDir),
            '--volume', sprintf('%s/data/gtfs-out:/gtfs-out', $hostProjectDir),
            'ghcr.io/ad-freiburg/pfaedle:latest',
            '-x', '/osm/sud-france.osm',
            '-i', sprintf('/gtfs/%s', $gtfsId),
//            '--inplace',
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            $io->error('Echec du pfaedle');
            $this->logger->error('Echec du pfaedle - ' . $process->getErrorOutput());
            return Command::FAILURE;
        }
        $pfaedleEndTime = hrtime(true);
        $pfaedleTime = ($pfaedleEndTime - $pfaedleStartTime) / 1e+9;
        $io->writeln(sprintf('Pfaedle terminé en %.03f secondes', $pfaedleTime));



        $processEndTime = hrtime(true);
        $processTime = ($processEndTime - $processStartTime) / 1e+9;
        $io->writeln(sprintf('Processus terminé en %.03f secondes', $processTime));
        $io->success('Génération terminée.');

        $this->logger->info('Test command - Fin');

        return Command::SUCCESS;
    }
}
