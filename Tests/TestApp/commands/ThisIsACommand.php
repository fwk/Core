<?php
namespace TestApp\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fwk\Core\ServicesAware, Fwk\Core\ContextAware;

class ThisIsACommand extends Command implements ContextAware
{
    protected $context;

    protected function configure()
    {
        $this->setName('test')
            ->setDescription('Test command')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Who do you want to greet?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        if ($name) {
            $text = 'Hello '.$name;
        } else {
            $text = 'Hello';
        }

        $output->writeln($text);
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     *
     * @param Context $context
     */
    public function setContext(\Fwk\Core\Context $context)
    {
        $this->context = $context;
    }
}