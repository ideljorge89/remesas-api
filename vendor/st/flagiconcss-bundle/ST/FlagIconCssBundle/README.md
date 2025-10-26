# Flag-icon-css Bundle for Symfony2

## Installation

### Add bundle to your composer.json file

``` js
// composer.json

{
    "require": {
		// ...
        "st/flagiconcss-bundle": "~1.0"
    }
}
```

### Add bundle to your application kernel

``` php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new ST\FlagIconCssBundle\STFlagIconCssBundle(),
        // ...
    );
}
```

### Download the bundle using Composer

``` bash
$ php composer.phar update st/flagiconcss-bundle
```

### Install assets

Given your server's public directory is named "web", install the public vendor resources

``` bash
$ php app/console assets:install web
```

Optionally, use the --symlink attribute to create links rather than copies of the resources 

``` bash
$ php app/console assets:install --symlink web
```

## Usage

Refer to the desired files in your HTML template, e.g.

``` html
<link rel="stylesheet" type="text/css" href="{{ asset('bundles/stflagiconcss/css/flag-icon.min.css') }}" />
```

# Licenses

Refer to the source code of the included files for license information

# References

1. http://lipis.github.io/flag-icon-css/
