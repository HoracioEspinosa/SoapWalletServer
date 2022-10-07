## Instalation


First you need download and install symfony cli for run the project and composer

```
composer install
``` 

Run migrations
```
php bin/console doctrine:migrations:migrate
```

Run project
```
symfony server:start
``` 

For review the docs and examples how run the endpoint please go to docs/ePayco-soapui-project.xml and open the project file with SoapUI