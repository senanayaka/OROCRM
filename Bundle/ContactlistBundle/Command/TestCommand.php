<?php

namespace Talliance\Bundle\ContactlistBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\HttpFoundation\Request;

use Trackside\Bundle\TicketingBundle\Entity\Ticket;

class TestCommand extends ContainerAwareCommand
{
    const COMMAND_NAME   = 'oro:cron:oro-cron-get_contact_us_list';

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/1 * * * *';
    }

    /**
     * Console command configuration
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('OroCRM Get Contact us List - CRON job')
            ->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();

        $customer = $manager->getRepository('TracksideCustomerBundle:Customer')->find(1);
        $manager->flush();
        $i=0;

        while($i<=15000) {
            $ticket = new Ticket();
            $ticket->setCode('RAJ TEST TICKET'.$i);
            $ticket->setCategory('ga');
            $ticket->setHolderName('Ajitesh Raj');
            $ticket->setCreated(new \DateTime('now'));
            $ticket->setModified(new \DateTime('now'));
            $ticket->setCustomer($customer);
            $manager->persist($ticket);

            $i++;
        }
        $manager->flush();
    }
}