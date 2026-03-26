# View Details

- **Name:** {!! $name !!}
- **Path:** {!! $path !!}
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
@if(!empty($composers))

## Composers

@foreach($composers as $composer)
* {!! $composer['name'] !!} ({!! $composer['type'] ?? 'class' !!})
@endforeach
@endif
@if(!empty($data))

## Data

```json
{!! json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
```
@endif
