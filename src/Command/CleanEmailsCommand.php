<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class CleanEmailsCommand extends Command
{
    protected static $defaultName = 'app:clean-emails';

    // 2. Expose the EntityManager in the class level
    private $entityManager;
    private $parameter;

    public function __construct(EntityManagerInterface $entityManager,ParameterBagInterface $parameterBag)
    {
        // 3. Update the value of the private entityManager variable through injection
        $this->entityManager = $entityManager;
        $this->parameter     = $parameterBag;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import Clean Step 2 - Validate Emails')
            ->addArgument('entity_name', InputArgument::REQUIRED, 'What is the database name that we will clean?');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $entity_name = $input->getArgument('entity_name');
        $query_1 = "App\Entity\\$entity_name";

        $em = $this->entityManager;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $q_count = $em->createQuery("SELECT COUNT(u.id) FROM $query_1 u WHERE u.email <> '' ");
        $record_count = $q_count->getSingleScalarResult();

        $progress1 = new ProgressBar($output, $record_count);
        $progress1->start();
        $progress1->setFormat("%current%/%max% [%bar%] %percent:3s%% - %estimated:-6s%  %memory:6s%");
        $output->writeln(' ');

        $q = $em->createQuery("SELECT u.id FROM $query_1 u  WHERE u.email <> '' ");
        $iterableResult = $q->toIterable();

        $i = 1;
        $batchSize = 20000;

        $bad_emails = [];
        foreach ($iterableResult as $key => $row) {
            $progress1->advance();
            $query = $em->createQuery("SELECT u FROM {$query_1} u WHERE u.id = :id ")->setParameter('id', $row['id']);
            $voter = $query->getSingleResult();

            $output->writeln($voter->getEmail());

            $email = $voter->getEmail();
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $output->writeln('EMAIL IS VALID: '.$voter->getEmail());
            } else {
                $output->writeln('NOT A VALID EMAIL: '.$voter->getId().': '.$voter->getEmail());
                $bad_emails[$key]['id'] = $voter->getId();
                //$bad_emails[$key]['voter_id'] = $voter->getVoterId();
                $bad_emails[$key]['voter_id'] = 'N/A';
                $bad_emails[$key]['email'] = $voter->getEmail();

            }

            // $voter->setNamePrefix(ucwords(strtolower($voter->getNamePrefix())));
            // $voter->setNameLast(ucwords(strtolower($voter->getNameLast())));
            // $voter->setNameFirst(ucwords(strtolower($voter->getNameFirst())));
            // $voter->setNameMiddle(ucwords(strtolower($voter->getNameMiddle())));
            // $em->persist($voter);
            /*
            if (($i % $batchSize) === 0) {
                $em->flush(); // Executes all updates.
                $em->clear(); // Detaches all objects from Doctrine!
                unset($voter);
                sleep(6);
            }*/
            ++$i;


        }
        // $em->flush();
        // $em->clear();
        $progress1->finish();

        // count($bad_emails);
        $output->writeln(count($bad_emails) . ' Bad Emails');

        //save CSV File
        $filesystem = new Filesystem();
        if (!$filesystem->exists($this->parameter->get('data'))) { //if not exit
            $filesystem->mkdir($this->parameter->get('data')); // make folder
        }

        $filePath  = $this->parameter->get('data').'/'.strtotime(date('Y-m-d H:i:s')).'.csv';
        $fp = fopen($filePath, 'w');
        fputcsv($fp, array('id', 'voter_id', 'email'), ',');
        foreach ($bad_emails as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        $io->success('All data names have been cleaned out! Success');
        return Command::SUCCESS;
    }

}
