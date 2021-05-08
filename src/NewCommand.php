<?php

namespace WordPressGitInstaller\Console;

use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new WordPress Site Repository')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('wpdir', null, InputOption::VALUE_OPTIONAL, 'Installs the WordPress Submodule in the specified Directory')
            ->addOption('wpversion', null, InputOption::VALUE_OPTIONAL, 'Installs the specified WordPress version');
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileSystem = new Filesystem();
        $directory = ($input->getArgument('name')) ? getcwd() . '/' . $input->getArgument('name') : getcwd();
        $wpdirectory = ($input->getOption('wpdir')) ? $input->getOption('wpdir') : 'wordpress';
        $wpversion = ($input->getOption('wpversion')) ? $input->getOption('wpversion') : $this->getWordPressVersion();

        $this->verifyApplicationDoesntExist($directory);

        if ($directory !== getcwd()) {
            $fileSystem->mkdir($directory);
            chdir($directory);
        }

        $commands = [
            'git init -q',
            'git submodule --quiet add git@github.com:WordPress/WordPress.git ' . $wpdirectory,
            'cd ' . $wpdirectory,
            'git fetch --tags -q',
            'git checkout ' . $wpversion . ' -q',
            'echo Current WordPress version is: ' . $wpversion
        ];

        $output->writeln('<info>Initializing an Empty Repository & Downloading WordPress from Git ...</info>');

        $process = Process::fromShellCommandline(implode(' && ', $commands), $directory, null, null, null);
        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<info>Git Download Complete</info>');

        $output->writeln('<info>Repository Cleanup Started</info>');
        $this->moveWordPressContent($directory, $wpdirectory, $fileSystem, $output);

        $this->cleanUpWordPressContent($directory, $wpdirectory, $fileSystem, $output);
        $output->writeln('<info>Repository Cleanup Completed</info>');

        $output->writeln('<comment>WordPress Site Setup ready! Build something amazing.</comment>');

        return $process->getExitCode();
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('WordPress Site already exists!');
        }
    }

    /**
     * Move WordPress Files and Folder to the proper location
     *
     * @param  string  $directory
     * @param  string  $wpdirectory
     * @param  \Symfony\Component\Filesystem\Filesystem  $fileSystem
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function moveWordPressContent($directory, $wpdirectory, Filesystem $fileSystem, OutputInterface $output)
    {
        $fileSystem->mirror(getcwd() . '/' . $wpdirectory . '/wp-content', $directory . '/wp-content');
        $fileSystem->copy(getcwd() . '/' . $wpdirectory . '/wp-config-sample.php', $directory . '/wp-config.php');
        $fileSystem->copy(getcwd() . '/' . $wpdirectory . '/index.php', $directory . '/index.php');

        $output->writeln('<info>Moved WordPress Files</info>');
    }

    /**
     * Cleanup WordPress Files
     *
     * @param  string  $directory
     * @param  string  $wpdirectory
     * @param  \Symfony\Component\Filesystem\Filesystem  $fileSystem
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function cleanUpWordPressContent($directory, $wpdirectory, Filesystem $fileSystem, OutputInterface $output)
    {
        $fileSystem->remove(getcwd() . '/' . $wpdirectory . '/wp-config-sample.php');
        $fileSystem->remove(getcwd() . '/' . $wpdirectory . '/index.php');
        $fileSystem->remove(getcwd() . '/' . $wpdirectory . '/wp-content');

        $fileSystem->remove($directory . '/wp-content/plugins/hello.php');
        $fileSystem->remove($directory . '/wp-content/themes/twentyten');
        $fileSystem->remove($directory . '/wp-content/themes/twentyeleven');
        $fileSystem->remove($directory . '/wp-content/themes/twentytwelve');
        $fileSystem->remove($directory . '/wp-content/themes/twentythirteen');
        $fileSystem->remove($directory . '/wp-content/themes/twentyfourteen');
        $fileSystem->remove($directory . '/wp-content/themes/twentyfifteen');
        $fileSystem->remove($directory . '/wp-content/themes/twentysixteen');
        $fileSystem->remove($directory . '/wp-content/themes/twentyseventeen');

        $output->writeln('<info>Removed WordPress Plugins and Theme Files</info>');

        $this->enterCustomConfiguration($directory, $wpdirectory, $fileSystem);

        $output->writeln('<info>Configuring WordPress</info>');
    }

    /**
     * Customize WordPress Configuration Files
     *
     * @param  string  $directory
     * @param  string  $wpdirectory
     * @param  \Symfony\Component\Filesystem\Filesystem  $fileSystem
     * @return void
     */
    protected function enterCustomConfiguration($directory, $wpdirectory, Filesystem $fileSystem)
    {
        $finder = new Finder();

        $contants = '// ** WordPress Configuration Constants ** //' . PHP_EOL;
        $contants .= 'define(\'WP_SITEURL\', \'https://\' . $_SERVER[\'SERVER_NAME\'] . \'/' . $wpdirectory . '\');' . PHP_EOL;
        $contants .= 'define(\'WP_HOME\', \'https://\' . $_SERVER[\'SERVER_NAME\']);' . PHP_EOL;
        $contants .= 'define(\'WP_CONTENT_DIR\', $_SERVER[\'DOCUMENT_ROOT\'] . \'/wp-content\');' . PHP_EOL;
        $contants .= 'define(\'WP_CONTENT_URL\', \'https://\' . $_SERVER[\'SERVER_NAME\'] . \'/wp-content\');' . PHP_EOL . PHP_EOL;
        $contants .= '// ** MySQL settings - You can get this info from your web host ** //';

        $finder->files()->in($directory)->name('*.php')->depth('== 0');

        foreach ($finder as $file) {
            $contents = $file->getContents();
            $replace = str_replace(
                '/wp-blog-header.php',
                '/' . $wpdirectory . '/wp-blog-header.php',
                str_replace(
                    '// ** MySQL settings - You can get this info from your web host ** //',
                    $contants,
                    $contents
                )
            );
            $fileSystem->dumpFile($file->getRealPath(), $replace);
        }
    }

    /**
     * Get the latest WordPress version tag name.
     *
     * @return string
     */
    protected function getWordPressVersion()
    {
        $client = new Client([ 'verify' => false]);
        $response = $client->get('//api.github.com/repos/WordPress/WordPress/tags');
        $versions = json_decode($response->getBody(), true);
        return $versions[0]['name'];
    }
}
