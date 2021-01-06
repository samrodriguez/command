## Symfony 5

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
