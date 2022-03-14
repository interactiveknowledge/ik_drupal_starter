# ik_d8_module_core

A core module for IK clients that contains custom plugins and modules. 

## Plugins

### Migrate

#### File Entity

Source plugin for migrate module that converts file entities in the source (Drupal 7) to media entities (Drupal 8)

#### File Lookup

Takes a file uri from Drupal 7 and matches it to an already imported file entity.

#### Youtube

Process plugin to migrate youtube media to new media entities.

### Rest

#### Metatag Resource

Returns metatag information as a custom endpoint.

### Views

#### Jsonapi Views Serializer

The style plugin to output views in serialized jsonapi format

### Modules

#### IK Dashboard

Dashboard and documentation module for IK Clients.

#### IK Entity Reference Delete

Deletes orphaned entity references when the entity itself has been deleted.

### Themes

#### IK D8 Theme API

Default IK theme for Decoupled Drupal Sites.
