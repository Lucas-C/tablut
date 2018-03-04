# Tablut

[![Build Status](https://travis-ci.org/Lucas-C/tablut.svg?branch=master)](https://travis-ci.org/Lucas-C/tablut)
[![Waffle.io - Columns and their card count](https://badge.waffle.io/Lucas-C/tablut.svg?columns=all)](https://waffle.io/Lucas-C/tablut)

Tablut board game implementation for [BoardGameArena](https://boardgamearena.com).

![Board screenshot](board_screenshot.png)

Rules:
- [in English](http://en.doc.boardgamearena.com/Gamehelptablut)
- [in French](http://fr.doc.boardgamearena.com/Gamehelptablut)

Note: The player invited to join the game currently always plays the king.

## Game state machine

Very basic:

![4-states simple state machine](GameStateMachine.png)


# Development

Player 1 is the king.

## Installation
```
composer install
npm install -g eslint eslint-config-strict eslint-plugin-filenames stylelint
npm install stylelint-config-standard
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
