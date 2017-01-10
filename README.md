# Pitech Mssql Bundle
This bundle contains a tweak of [mediamonks/symfony-mssql-bundle](https://github.com/mediamonks/symfony-mssql-bundle) to ensure compatibility with Microsoft Sql Server 2005
This readme is based on the original one at [mediamonks github](https://github.com/mediamonks/symfony-mssql-bundle/blob/master/Resources/doc/1-setting_up_the_bundle.rst)

# Installation
A) Add the Pitech bundle to the project
--------------------
~~~~
    composer require pitech-mures/mssqlbundle
~~~~
    
B) Initialize the Pitech and Mediamonks bundles in AppKernel.php
--------------------
~~~~
    new MediaMonks\MssqlBundle\MediaMonksMssqlBundle(),
    new Pitech\MssqlBundle\PitechMssqlBundle(),
~~~~


C) Enable the driver
--------------------

Now you should be able to enable the driver by updating your
Doctrine DBAL config in the ``app/config/config.yml`` so it looks like this:
~~~~
    doctrine:
        dbal:
            driver_class: Pitech\MssqlBundle\DBAL\Driver\PDODblib\Driver
            wrapper_class: MediaMonks\MssqlBundle\Doctrine\DBAL\Connection
            host:     "%database_host%"
            port:     "%database_port%"
            dbname:   "%database_name%"
            user:     "%database_user%"
            password: "%database_password%"
            charset:  UTF-8
~~~~

D) Enable the DB session handler
======================================

Since the default PDO Session Handler provided by Symfony does not support ``pdo_dblib``
a custom handler is needed. Luckily the configuring it is very similar as configuring the default one.

Open up ``app/services.yml`` and add these services:
~~~~
    services:
        pdo:
            class: MediaMonks\MssqlBundle\PDO\PDO
            arguments:
                host: "%database_host%"
                port: "%database_port%"
                dbname: "%database_name%"
                user: "%database_user%"
                password: "%database_password%"
                options:
            calls:
                - [setAttribute, [3, 2]] # \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION

        session.handler.pdo:
            class:     MediaMonks\MssqlBundle\Session\Storage\Handler\PdoSessionHandler
            public:    false
            arguments: ["@pdo"]
~~~~

E) Enable the Composer script
--------------------

Open your ``composer.json`` file and make sure the script is added to ``post-install-cmd`` and
``post-update-cmd``

~~~~
    "scripts": {
        "post-install-cmd": [
            "MediaMonks\\MssqlBundle\\Composer\\ScriptHandler::ensureDoctrineORMOverrides"
        ],
        "post-update-cmd": [
            "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::buildBootstrap"
        ]
    }
~~~~    
