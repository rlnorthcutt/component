**Component field module**

Refactor to extend block_field module
- extend BlockFieldSelectionManager.php (get widget options)
- extend BlockFieldWidget (naming and selection manager)
- extend BlockFieldType (no select options)

## TODO
- make sure enable field works
- find way to pass parent entity data to component data config
- find a way to author/edit the component config (probably widget?)
- what are the defaults when no config? (parent entity type, id, field name)
