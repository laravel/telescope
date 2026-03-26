# Log Details

- **Level:** {!! strtoupper($level) !!}
- **Message:** {!! $message !!}
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
@if(!empty($context))

## Context

```json
{!! json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
```
@endif
