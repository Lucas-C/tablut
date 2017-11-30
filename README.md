# Tablut

[![Build Status](https://travis-ci.org/Lucas-C/tablut.svg?branch=master)](https://travis-ci.org/Lucas-C/tablut)
[![Waffle.io - Columns and their card count](https://badge.waffle.io/Lucas-C/tablut.svg?columns=all)](https://waffle.io/Lucas-C/tablut)


Implémentation du jeu [Tablut](http://jeuxstrategieter.free.fr/Tablut_complet.php) pour [BoardGameArena](https://boardgamearena.com).

## Game state machine

Very basic:

![4-states simple state machine](GameStateMachine.png)


# Development

## Installation
```
composer install
npm install -g csslint eslint eslint-config-strict eslint-plugin-filenames
```

## Code validation
```
composer check-styles
composer test
composer bgaw-validate
```

## Deploying to Studio
```
cp bgaproject.yml.dist bgaproject.yml
# then fill in sftp properties
composer bgaw-deploy
```

## Continuous Deployment to Studio

Watches development files and deploys them as they change.
```
composer bgaw-watch
```
