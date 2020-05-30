**Component field module**

Provides a field type for adding a component to an entity. This will provide the author with a choice of which field enabled components to use to populate that field.

In order to enable a component for this field type, just add `enable_field: true` to the component's info.yml file.

## TODO
- make sure enable field works
- find way to pass parent entity data to component data config
- find a way to author/edit the component config (probably widget?)
- what are the defaults when no config? (parent entity type, id, field name)

