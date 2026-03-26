# Redis Command

- **Connection:** {!! $connection !!}
- **Duration:** {!! $time !!}ms
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')

## Command

```
{!! $command !!}
```
