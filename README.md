Symfony Task Manager Demo
=========================

A simple Symfony demo application to manage tasks (CRUD operations). Client side is built on Backbone-Marionette.


Requirements
------------

  * PHP 5.5 or higher;
  * PDO-SQLite PHP extension enabled;
  * NodeJS & NPM

Installation
------------

```bash
$ git clone https://github.com/m1ch3lp3r3z/symfony-tasks-manager-demo
$ cd symfony-tasks-manager-demo/
$ composer install --no-interaction
$ php app/console doctrine:schema:create
$ php app/console doctrine:fixtures:load --no-interaction
```

You can skip next installation steps if you already have bower installed globally
```bash
$ npm install
$ node_modules/bower/bin/bower install
```

Usage
-----

```bash
$ php app/console server:run
```

Once local server is running go to [localhost:8000](http://localhost:8000/)

TODOs
-----

  * Modify browser url (pushState) when paginating or editing item
  * Allows to load initial app state from URL (pagination, editing)
  * Implement loading animation based on Spin js library (already installed)
  * Implement unit tests!!!
  * Implement API authentication

