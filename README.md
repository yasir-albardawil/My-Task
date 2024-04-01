## Drupal  Task

### Login credentials:
**Username:** admin
**Password:** admin123456

### Drupal 10 requmnets
-   MySQL 5.7.8 or higher
-  [Drupal 10 requires at least PHP 8.1](https://www.drupal.org/node/3264830).  [PHP 8.1.6 is recommended.](https://www.drupal.org/node/3295061)
### Database file: 

    database/db.sql
  ### Run composer
  

    composer install

 ### Druch commands
 
	./vendor/bin/drush cim 
    ./vendor/bin/drush local:export ar > web/sites/default/files/translations/ar.po
    ./vendor/bin/drush local:check
    ./vendor/bin/drush local:update
    ./vendor/bin/drush cr 

### settings.php

    $databases['default']['default'] = array(  
	  'database' => 'drupal_task_db',  
	  'username' => 'root',  
	  'password' => '',  
	  'prefix' => '',  
	  'host' => 'localhost',  
	  'port' => '3306',  
	  'isolation_level' => 'READ COMMITTED',  
	  'driver' => 'mysql',  
	  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',  
	  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',  
	);  
	$settings['config_sync_directory'] = 'sites/default/files/config_wZZgqsevDsNA5bgfR2WF3Uz2xu3iJbclLSdKP0BsCaCKiKTGMoAf3hAIuv1ijx6G-HDcn88Y8w/sync';  
	$settings['file_private_path'] = 'sites/default/files/tmp';  
	  
	# $config['system.logging']['error_level'] = 'verbose';  
	$config['basic.auth']['credentials'] = ['admin', 'admin123456'];

 

