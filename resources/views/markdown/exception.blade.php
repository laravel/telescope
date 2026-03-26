# Exception Details

- **Type:** {!! $class !!}
- **Message:** {!! $message !!}
- **Location:** {!! $file !!}:{!! $line !!}
@if(!empty($resolved_at))
- **Resolved At:** {!! $resolved_at !!}
@endif
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

## Stack Trace

```
@foreach($trace as $i => $frame)
{!! $i !!} - {!! $frame['file'] !!}:{!! $frame['line'] !!}
@endforeach
```

@include('telescope::markdown.partials.related')
