# rent collector

[![Build Status](https://travis-ci.org/mrsuh/rent-collector.svg?branch=master)](https://travis-ci.org/mrsuh/rent-collector)

## Installation
```sh
sh bin/install.sh
sh bin/deploy.sh
```

## Collect
```sh
php bin/console app:collect
```

## Configuration

config/parameters.yml
```yml
parameters: 
    #database
    database.hot.host: 127.0.0.1
    database.hot.port: 27017
    database.hot.name: rent-hot
    database.hot.user: root
    database.hot.password: pass

    #mailer
    mailer_transport: smtp
    mailer_host: 127.0.0.1
    mailer_user: null
    mailer_password: null
    
    #guzzle request paameters
    guzzle.timeout: 10
    guzzle.connect_timeout: 5
    
    #beanstalkd parameters
    queue.port: 9090
    queue.host: 127.0.0.1
```

config/parameters.request.yml
```yml
parameters:
    request.base_uri.tomita: 'http://parser.local'
    request.base_uri.vk: 'https://api.vk.com/method'
    request.vk.wall.period: '10 minutes'
```
Service [http://parser.local](https://github.com/mrsuh/rent-parser)

config/parameters.dir.yml
```yml
parameters:
    dir.tmp: '%kernel.root_dir%/../var/tmp'
```

config/parameters.log.yml
```yml
parameters:
    log.prod.level: debug
    log.consumer_collect.level: debug
    log.consumer_parse.level: debug
    log.consumer_publish.level: debug
    log.consumer_notify.level: error
```