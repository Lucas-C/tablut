# Tablut

[![Build Status](https://travis-ci.org/Lucas-C/tablut.svg?branch=master)](https://travis-ci.org/Lucas-C/tablut)

Implémentation du jeu [Tablut](http://jeuxstrategieter.free.fr/Tablut_complet.php) pour [BoardGameArena](https://boardgamearena.com).


## Running Tests

```
composer test
composer bga-validate
composer fix-styles
```

## Deploying to Studio
```
composer deploy
```

## Continuous Deployment to Studio

Watches development files and deploys them as they change.
```
bgawb build -w -d
```
