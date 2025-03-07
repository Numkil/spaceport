<?php

namespace Spaceport\Helpers;


use Spaceport\Model\Shuttle;
use Symfony\Component\Process\Process;

class Sf4InitInitHelper extends SfInitHelper
{

    public function getConfigDockerFilePath(): string
    {
        return "config/packages/docker/config.yaml";
    }

    public function getTwigTemplateNameConfigDockerFile(): string
    {
        return "symfony/config_docker_sf4.yaml.twig";
    }

    public function getDatabaseSettings(): array
    {
        return [];
    }

    public function getApacheDocumentRoot(): string
    {
        return "/app/public";
    }

    public function dockerizeApp(Shuttle $shuttle): void
    {
        $this->checkBundlesFile();
        $this->checkKernelFile();
        $this->createDockerRoutesFromDev();
        $this->createEnvFile($shuttle);
    }

    private function checkBundlesFile(): void
    {
        $this->logStep('Checking if the config/bundles.php file is setup for Docker');

        if (file_exists("config/bundles.php")) {
            $file = \file("config/bundles.php");

            foreach ($file as $key => $line) {
                if (preg_match("/\[[\'|\"]dev.*?\]/", $line, $matches) && strpos($line, "docker") === false) {
                    $match = $matches[0];
                    $matchReplace = substr_replace($match, ", 'docker' => true" . substr($match, -1), -1);
                    $file[$key] = str_replace($match, $matchReplace, $file[$key]);
                }
            }

            file_put_contents("config/bundles.php", implode("", $file));
        }
    }

    private function checkKernelFile(): void
    {
        $this->logStep('Checking if the src/Kernel.php file is setup for Docker');

        require getcwd().'/vendor/autoload.php';

        if (file_exists("src/Kernel.php")) {
            $file = $this->writeLogDir(\file("src/Kernel.php"));
            $file = $this->writeCacheDir($file);

            file_put_contents("src/Kernel.php", implode("", $file));
        }
    }

    private function createDockerRoutesFromDev(): void
    {
        if (file_exists('config/routes/dev/') && !file_exists('config/routes/docker'))
        {
            $this->logStep('Copying dev routes to docker routes');
            $process = Process::fromShellCommandline('cp -r config/routes/dev config/routes/docker');
            $process->run();
            if (!$process->isSuccessful()) {
                $this->logError($process->getErrorOutput());

            }
        }
    }

    private function createEnvFile(Shuttle $shuttle): void
    {
        if (!file_exists('.env.docker')) {
            $this->logStep('Creating .env.docker file');
            $this->twig->renderAndWriteTemplate('symfony/sf4/env.docker.twig', '.env.docker', ['shuttle' => $shuttle]);
        } else {
            $this->logStep('.env.docker file already exists. Skipping!');
        }
    }

    private function writeLogDir(array $file): array
    {
        return $this->writeDir("App\Kernel", "getLogDir", $file,"/tmp/symfony/var/logs/");
    }

    private function writeCacheDir(array $file): array
    {
        return $this->writeDir("App\Kernel", "getCacheDir", $file,"/tmp/symfony/var/cache/");
    }
}
