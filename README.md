# rent collector

[![Build Status](https://travis-ci.org/mrsuh/rent-collector.svg?branch=master)](https://travis-ci.org/mrsuh/rent-collector)

## Installation
```sh
sh bin/install
sh bin/deploy
```

## Collect
```sh
php bin/console app:collect
```

## Configuration

config/parameters.yml
```yml
parameters:
    
    #hot database
    database.hot.host: 127.0.0.1
    database.hot.port: 27017
    database.hot.name: rent-hot
    database.hot.user: root
    database.hot.password: pass

    #cold database
    database.cold.host: 127.0.0.1
    database.cold.port: 27017
    database.cold.name: rent-cold
    database.cold.user: root
    database.cold.password: pass

    mailer_transport: smtp
    mailer_host: 127.0.0.1
    mailer_user: null
    mailer_password: null

    #guzzle request paameters
    guzzle.timeout: 10
    guzzle.connect_timeout: 5
```

config/parameters.request.yml
```yml
parameters:
    request.base_uri.tomita: 'http://parser.local'
    request.base_uri.vk: 'https://api.vk.com/method'
```
Service [http://parser.local](https://github.com/mrsuh/rent-parser)

config/parameters.dir.yml
```yml
parameters:
    dir.tmp: '%kernel.root_dir%/../var/tmp'
    file.config.parser: '%kernel.root_dir%/config/parser.yml'
    file.black_list.person: '%kernel.root_dir%/config/black_list.person.yml'
    file.black_list.description: '%kernel.root_dir%/config/black_list.description.yml'
    file.fixtures.subway: '%kernel.root_dir%/fixtures/subway.yml'

```

config/parser.yml
```yml
    type: vk.com:comment
    name: 'name'
    link: 'https://site.com/link'
    city: spb
    data:
        group_id: 100
        topic_id: 100
        count: 100
```
```yml        
    type: vk.com:wall
    name: 'name'
    link: 'https://site.com/link'
    city: spb
    date: '1 hour'
    data:
        owner_id: -100
        count: 50
```

config/black_list.person.yml
```yml
- user id
```

config/black_list.description.yml
```yml
- description text
```