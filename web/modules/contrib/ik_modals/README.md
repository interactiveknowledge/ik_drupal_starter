# ik_modals

Drupal 8 Module for managing site modals.
Maintained and supported by
[Interactive Knowledge](https://interactiveknowledge.com).

## INTRODUCTION

This module creates a custom entity (Modal) and allows
for different bundles. All are fieldable and provide
template suggestions for each bundle type.

## REQUIREMENTS

* An API key from [ipdata.co](https://ipdata.co) or use of
GeoIP2 library. (Configuration in module settings)
* Block Module
* [Address Module](https://www.drupal.org/project/address)

## INSTALLATION

Installation via composer is required to manage GeoIP2 library dependencies. See [Using Composer to install Drupal](https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies) for more information.

## CONFIGURATION

To enable geolocation targeting, add your registered API key
from [ipdata.co](https://ipdata.co) at
Configuration > Modal Settings.

Go to Structure > Block Layout and place the Modals Block
anywhere on the page. Make sure to uncheck the Display title
option. Make sure that the permissions for "View published
Modal entities" are set for the proper roles.
(Ex: If you want anonymous users to view the active modals,
you have to set the permission).
