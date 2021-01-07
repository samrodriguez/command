## Symfony 5
```shell
1. Mount data base dump-command-202101071005.sql
2. Clone this git
3. Move on the project "command" 
4. run composer install 
5. run the command: php bin/console app:clean-emails Emails

Then you can see in ../public/data the csv file
```

## 1 config/service.yml
```shell
parameters:
    data: '%kernel.project_dir%/public/data'
```

## 2  command/src/Command/CleanEmailsCommand.php
Add ParameterBagInterface
```shell
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
```

```shell
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
```
