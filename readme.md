# Underpin Dependency Compiler

This utility will automatically take all Underpin dependencies and replace them with a different chosen name. This makes
it possible to package Underpin in a way that prevents unexpected collissions with other plugins that may also be using
a different version of Underpin.

The intent of this compiler is to be _extremely basic_, so there's no configuration. In fact, this all this compiler
does right now is literally rename all references to Underpin with the name you specify.

Right now, this assumes that you are working with Composer, and does not support builds that are not using Composer,
however that could be changed in the future.

## Installation

`composer require underpin/compiler`

## Usage

`vendor/bin/package-underpin.php customized_name`