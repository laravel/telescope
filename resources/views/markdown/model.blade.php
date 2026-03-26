# Model Details

- **Action:** {!! $action !!}
- **Model:** {!! $model !!}
@if(isset($count))
- **Count:** {!! $count !!}
@endif
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
@if(!empty($changes))

## Changes

```json
{!! json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
```
@endif
