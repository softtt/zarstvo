Project setup
-------------
* Clone .git repository git@bitbucket.org:aprex/zarstvo-prestashop.git
* Download [Mysql dump and images archives](https://drive.google.com/folderview?id=0BxYA2-vhhBdhWHZuY0lDakl3TVU&usp=sharing)
* Setup local environment or (preferably) Setup and use Vagrant image
  * Setup local environment:
    Requirements: PHP >= 5.4, Mysql, Apache
  * Setup Vargant environment:
    * Go to vargant/puphpet folder; you'll find there "config.yaml" file, which stores all needed virtual box and vagrant box settings.
      You can easily override any setting in "config-custom.yaml" file.
      At start you need to set fullpath to project root directory in "vflsf_9z3hnxdd1xzb: sourse:" option
      You'll have full environment with all installed and configured PHP, Apache and virtual host, Mysql server
    * Add host and ip address to your OS hosts file (ip and hostname could be changed in config.yaml):
      192.168.10.10 zarstvo-shop.local
* Import MySQL dump database
  Note: Via Vagrant you need to put mysql dump into project root folder, connect to virtual machine via ssh and import to mysql using command line mysql import
* Configure Prestashop
  * Create config/defines.inc.php (by copying .example file)
  * Create config/settings.inc.php; setup database connection (by copying .example file)
  * Log in to admin area and change Shop URL to your local value
  * Check Prestashop configuration check and issues
  * Insert "img" and "modules" folders, not tracked by git from backup zip archives
* On the first site access you'll need to go to admin panel and change shop URL setting. Otherwise you'll be redirected from your local to URL set in system.

Vagrant 'How to'
----------------
Note: all this commands should be used in command line.

To start virtual machine go to "vagrant" folder and run "vagrant up"
To stop virtual machine go to "vagrant" folder and run "vagrant halt"
To connect to virtual machine via SSH go to "vagrant" folder and run "vagrant ssh"

Common notes
------------
Admin back office can be accessed on route zarstvo-shop.local/admin129virvup
Admin credentials on test sample database:
  login: admin@example.com
  password: 11111111
