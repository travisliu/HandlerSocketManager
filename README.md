HandlerSocketLib is a HandlerSocket tool for PHP, It's intention is to make working with HandlerSocket and manage connection as simple as possible.

Requirements
-------

- PHP 5.3 or greater
- php-handlersocket

Getting Started
------------

### set up database

HandlerSocketLib support mysql read write splitting to achieve load balance. You can just setup master server, if you just have one database server
```php
  class HandlerSocketManager extends HandlerSocket { 
    private function init(){
      $master = '192.168.1.1';
      $slave = array(
        array('host' => '192.168.1.2', 'proportion' => 1),
        array('host' => '192.168.1.3', 'proportion' => 1)
      );
    }
  }
```

### select

```php
$indexConnect =  Handler::factory($dataBase, $table, $index);
$result = $indexConnect->findOne($where, $column, $op);
dump($result);
```