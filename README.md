Project Fluent [![Build Status][travisimage]][travislink]
=========================================================

[travisimage]: https://travis-ci.org/tacoberu/fluent-php.svg?branch=master
[travislink]: https://travis-ci.org/tacoberu/fluent-php

This is a (***uncomplete***) PHP implementation of Project Fluent, a localization framework
designed to unleash the entire expressive power of natural language
translations.

Project Fluent keeps simple things simple and makes complex things possible.
The syntax used for describing translations is easy to read and understand.  At
the same time it allows, when necessary, to represent complex concepts from
natural languages like gender, plurals, conjugations, and others.


Learn the FTL syntax
--------------------

FTL is a localization file format used for describing translation resources.
FTL stands for _Fluent Translation List_.

FTL is designed to be simple to read, but at the same time allows to represent
complex concepts from natural languages like gender, plurals, conjugations, and
others.

    hello-user = Hello, { $username }!

[Read the Fluent Syntax Guide][] in order to learn more about the syntax.  If
you're a tool author you may be interested in the formal [EBNF grammar][].

[Read the Fluent Syntax Guide]: http://projectfluent.org/fluent/guide/
[EBNF grammar]: https://github.com/projectfluent/fluent/tree/master/spec


Installation
------------

The recommended way to install is via Composer:

        composer require tacoberu/fluent-intl



Usage
-----

```php

require __dir__ . '/vendor/autoload.php';

use Taco\FluentIntl\FluentTranslator;
use Taco\FluentIntl\FluentResource;

$translator = new FluentTranslator("en-US");
$translator->addResource(new FluentResource('
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
greet-by-name = Hello, { $name }!
emails =
    { $unreadEmails ->
        [1] You have one unread email.
       *[other] You have { $unreadEmails } unread emails.
    }
'));

$msg = $translator->getMessage("welcome");
dump($translator->formatPattern($msg->value, ["name" => "Anna"]));
// Welcome, Anna, to Foo 3000!

$msg = $translator->getMessage("emails");
dump($translator->formatPattern($msg->value, ["unreadEmails" => 5]));
// You have 5 unread emails.

```


Status
------

Only part of the specification implemented.
