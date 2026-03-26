# Event Details

- **Event:** {!! $name !!}
@if(!empty($broadcast))
- **Broadcast:** Yes
@endif
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
@if(!empty($listeners))

## Listeners

@foreach($listeners as $listener)
* {!! is_array($listener) ? $listener['name'] ?? $listener : $listener !!}
@endforeach
@endif
@if(!empty($payload))

## Payload

```json
{!! json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
```
@endif
