# NYX Mercurius

NYX Mercurius é uma biblioteca para gerenciamento de projetos com WP-CLI.

[![Latest Stable Version](https://poser.pugx.org/nyx-solutions/nyx-mercurius/v/stable)](https://packagist.org/packages/nyx-solutions/nyx-mercurius)
[![Total Downloads](https://poser.pugx.org/nyx-solutions/nyx-mercurius/downloads)](https://packagist.org/packages/nyx-solutions/nyx-mercurius)
[![Latest Unstable Version](https://poser.pugx.org/nyx-solutions/nyx-mercurius/v/unstable)](https://packagist.org/packages/nyx-solutions/nyx-mercurius)
[![License](https://poser.pugx.org/nyx-solutions/nyx-mercurius/license)](https://packagist.org/packages/nyx-solutions/nyx-mercurius)
[![Monthly Downloads](https://poser.pugx.org/nyx-solutions/nyx-mercurius/d/monthly)](https://packagist.org/packages/nyx-solutions/nyx-mercurius)
[![Daily Downloads](https://poser.pugx.org/nyx-solutions/nyx-mercurius/d/daily)](https://packagist.org/packages/nyx-solutions/nyx-mercurius)
[![composer.lock](https://poser.pugx.org/nyx-solutions/nyx-mercurius/composerlock)](https://packagist.org/packages/nyx-solutions/nyx-mercurius)

## Instalação

O método indicado para instalação da biblioteca é o [composer](http://getcomposer.org/download/).

Rode

```bash
php composer.phar require --prefer-dist nyx-solutions/nyx-mercurius "*"
```

ou adicione

```
"nyx-solutions/nyx-mercurius": "*"
```

à seção `require` do seu arquivo `composer.json`.

## Comandos do Mercurius

### Global

Para utilizar os comandos desta biblioteca, adicione o trecho abaixo em seu arquivo `wp-config.php` após as definições 
de constantes (o trecho `/** @noinspection PhpFullyQualifiedNameUsageInspection */` pode se você utilizar 
`use nyx\mercurius\wp\cli\Mercurius` no início do arquivo):

```php
<?php

    \nyx\mercurius\wp\cli\Mercurius::init(
        __DIR__,
        __DIR__.'/wp-project',
        'https://plugins.wpsite.tmp.br',
        'nyx',
        'passwd'
    );

```

Os três últimos parâmetro se referem a um repositório exclusivo de plugins (referenciado na configuração como este 
exemplo: `%PLUGINS_REPOSITORY%/my-plugin.zip`). O repositório deve estar com autenticação BASIC e o usuário e senha
liberados devem ser informados no segundo e terceiro parâmetros.

### Project

#### Version

Informa a versão atual do `nyx project`, exemplo:

```bash

wp nyx project version

```

#### Configure

Configura um projeto com base em um arquivo de configurações (`wp-project/environments.json`) e em um ambiente 
específico. O arquivo base de configuração deve ter a estrutura abaixo:

```json

{
  "global": {
    "update_core": true,
    "update_languages": true,
    "update_themes": true,
    "update_plugins": true,
    "default_plugins": {
      "my-plugin": "%PLUGINS_REPOSITORY%/my-plugin.zip",
      "better-wp-security": true,
      "wp-crontrol": true
    },
    "default_themes": {
      "twentytwentyone": true
    }
  },
  "development": {
    "host": "site.wpsite.tmp.br",
    "disabled_plugins": [
      "wp-crontrol"
    ]
  },
  "staging": {
    "host": "staging.wpsite.com.br",
    "disabled_plugins": [
      "wp-crontrol"
    ]
  },
  "production": {
    "host": "www.wpsite.com.br",
    "disabled_plugins": [
      "wp-crontrol"
    ]
  }
}

```

Exemplo de execução do comando:

```bash
wp nyx project configure --env=development
```

## Changelog

### 1.1.3

- **ProjectCommand**: melhorias no carregamento e execução do comando.
- **Mercurius**: melhorias no carregamento e execução do comando.

### 1.1.2

- **ProjectCommand**: diversas correções no código.

### 1.1.1

- **ProjectCommand**: antes de executar o comando `plugin uninstall`, é verificado se o plugin está instalado.

### 1.1.0

- Primeira versão estável.

## License

**nyx-mercurius** é distribuído sob a licença BSD 3-Clause. Verifique o arquivo `LICENSE.md` para detalhes.
