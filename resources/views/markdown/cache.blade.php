# Cache Details

- **Action:** {!! ucfirst($type) !!}
- **Key:** {!! $key !!}
@if(isset($expiration))
- **Expiration:** {!! $expiration !!}s
@endif
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
@if(isset($value))

## Value

```json
{!! is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
```
@endif
