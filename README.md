##  config/service.yml
```shell
parameters:
    data: '%kernel.project_dir%/public/data'
```

## command/src/Command/CleanEmailsCommand.php
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
