<?php

namespace Spaceport\Commands;

use Spaceport\Model\Shuttle;
use Spaceport\Traits\IOTrait;
use Spaceport\Traits\TwigTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class AbstractCommand extends Command
{
    public const DOCKER_COMPOSE_FILE_NAME = "docker-compose.yml";

    use TwigTrait;
    use IOTrait;

    protected Shuttle $shuttle;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setUpIO($input, $output);
        $this->setUpTwig($output);

        $this->showLogo();
        $this->io->title("Executing " . get_class($this));

        $this->shuttle = new Shuttle();

        $this->checkDockerDaemonIsRunning();
        $this->isApacheRunning();

        $this->doPreExecute($output);
        return $this->doExecute($input, $output);
    }

    protected function runCommand($command, $timeout = 180, $env = [], $quiet = false): bool|string
    {
        $this->logCommand($command);
        $env = array_replace($_ENV, $_SERVER, $env);
        $process = Process::fromShellCommandline($command, null, $env);
        $process->setTimeout($timeout);
        $process->run(function ($type, $buffer) {
            if ($this->output->getVerbosity() > OutputInterface::VERBOSITY_VERBOSE) {
                echo $buffer;
            }
        });
        if (!$process->isSuccessful()) {
            if (!$quiet) {
                $this->logError($process->getErrorOutput());
            }

            return false;
        }

        return trim($process->getOutput());
    }

    protected function isDockerized($quiet = false): bool
    {
        $dockerFile = $this->getDockerComposeFileName();
        if (!file_exists($dockerFile)) {
            if (!$quiet) {
                $this->logError("There is no docker-compose file present. Run `spaceport init` first");
            }

            return false;
        }

        return true;
    }

    protected function getDockerComposeFileName(): string
    {
        return self::DOCKER_COMPOSE_FILE_NAME;
    }

    protected function getDockerComposeFullFileName(): string
    {
        $currentWorkDir = getcwd();
        $dockerFilePath = $currentWorkDir . DIRECTORY_SEPARATOR;
        $dockerFile = $this->getDockerComposeFileName();

        return $dockerFilePath . $dockerFile;
    }

    protected function isOwnerOfFilesInDirectory(): bool
    {
        $uid = $this->runCommand('id -u');
        $path = getcwd();
        $files = scandir($path);
        foreach ($files as $file) {
            if (!in_array($file, ['.', '..'])) {
                $info = stat($path . '/' . $file);
                $fileUid = $info[4];
                if ((int)$fileUid !== (int)$uid) {
                    $this->logWarning("You are not the filesystem owner of some files/directories in the project.");
                    return false;
                }
            }
        }

        return true;
    }

    private function checkDockerDaemonIsRunning(): void
    {
        $output = $this->runCommand('ps aux | grep docker | grep -v \'grep\' | grep -v \'com.docker.vmnetd\'', null, [], true);
        if (empty($output)) {
            $this->logError("Docker daemon is not running. Start Docker first before using spaceport");

            exit(1);
        }
    }

    private function isApacheRunning(): void
    {
        $process = Process::fromShellCommandline('pgrep httpd');
        $process->start();
        $process->wait();
        $processes = $process->getOutput();
        if (!empty($processes)) {
            // Only exit and show error on spaceport start
            if ($this instanceof StartCommand) {
                $this->logError('Apache seems to be running. Please shutdown apache and re-run your command.');
                exit(1);
            }
        }
    }

    private function doPreExecute()
    {
    }

    abstract protected function doExecute(InputInterface $input, OutputInterface $output);
}
