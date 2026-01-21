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
    description: 'Pipeline de génération GTFS + Map',
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->logger->info('Test command - Début');
        $processStartTime = hrtime(true);

        $hostProjectDir = $_ENV['HOST_PROJECT_DIR'] ?? getcwd();

        $id = str_replace('.', '_', uniqid('client_', true));
        $gtfsFilename = sprintf('%s.zip', $id);
        $svgFilename = sprintf('%s.svg', $id);

        //
        //   COPIE
        //
        $io->writeln('Copie du gtfs...');
        $copyStartTime = hrtime(true);

        $process = new Process(['cp', './data/gtfs/gtfs-aix.zip', sprintf('./data/gtfs/%s', $gtfsFilename)]);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Impossible de copier le gtfs');
            return Command::FAILURE;
        }

        $copyTime = (hrtime(true) - $copyStartTime) / 1e+9;
        $io->writeln(sprintf(' <info>OK</info> Copie terminée en %.03f s', $copyTime));


        //
        //   PFAEDLE
        //
        $io->writeln(sprintf('Lancement de Pfaedle sur %s', $gtfsFilename));
        $pfaedleStartTime = hrtime(true);

        $process = new Process([
            'docker', 'run', '-i', '--rm',
            '--platform', 'linux/amd64',
            '--volume', sprintf('%s/data/osm:/osm', $hostProjectDir),
            '--volume', sprintf('%s/data/gtfs:/gtfs', $hostProjectDir),
            '--volume', sprintf('%s/data/gtfs-out:/gtfs-out', $hostProjectDir),
            'ghcr.io/ad-freiburg/pfaedle:latest',
            '-x', '/osm/sud-france.osm',
            '-i', sprintf('/gtfs/%s', $gtfsFilename),
            '--inplace'
        ]);

        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Echec du pfaedle');
            $this->logger->error($process->getErrorOutput());
            return Command::FAILURE;
        }

        $pfaedleTime = (hrtime(true) - $pfaedleStartTime) / 1e+9;
        $io->writeln(sprintf(' <info>OK</info> Pfaedle terminé en %.03f s', $pfaedleTime));


        //
        //   LOOM
        //
        $io->writeln('Lancement de Loom...');
        $loomStartTime = hrtime(true);

        $bashCommand = sprintf(
            'gtfs2graph -m tram /data/gtfs/%s | topo | loom | octi | transitmap > /data/gtfs-out/%s',
            $gtfsFilename,
            $svgFilename
        );

        $process = new Process([
            'docker', 'run', '-i', '--rm',
            // On mappe tout le dossier data pour simplifier l'accès (in/out)
            '--volume', sprintf('%s/data:/data', $hostProjectDir),
            'loom',
            '/bin/bash', '-c', $bashCommand
        ]);

        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Echec du Loom');
            $this->logger->error($process->getErrorOutput());
            return Command::FAILURE;
        }

        $loomTime = (hrtime(true) - $loomStartTime) / 1e+9;
        $io->writeln(sprintf(' <info>OK</info> Loom terminé en %.03f s', $loomTime));


        //
        // FIN
        //
        $processTime = (hrtime(true) - $processStartTime) / 1e+9;
        $io->success(sprintf('Génération complète terminée en %.03f s. Fichier : data/gtfs-out/%s', $processTime, $svgFilename));

        $this->logger->info('Test command - Fin');

        return Command::SUCCESS;
    }
}
