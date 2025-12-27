# Plugin Development Guide

## Security Requirements

1. **Location**: Plugins must be in `app/Plugins` or `plugins` directory
2. **Interface**: Must implement `PluginInterface`
3. **Validation**: All plugins are validated before loading
4. **Sandboxing**: Plugins run in isolated scope
5. **Configuration**: Plugin configs are sanitized

## Creating a Plugin

1. Create plugin directory: `plugins/payment/yourgateway`
2. Create `plugin.json`:
```json
{
  "name": "YourGateway",
  "type": "payment",
  "version": "1.0.0",
  "class": "App\\Plugins\\Payments\\YourGatewayPlugin"
}
```

3. Create plugin class implementing `PluginInterface`
4. Register in database via admin panel

## Security Best Practices

- Never use `eval()`, `exec()`, or similar functions
- Validate all user input
- Use Laravel's built-in validation
- Sanitize configuration values
- Log all plugin actions
- Use dependency injection
