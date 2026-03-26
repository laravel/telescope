# Command Details

- **Command:** {!! $command !!}
- **Exit Code:** {!! $exit_code !!}
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
@if(!empty($arguments))

## Arguments

```json
{!! json_encode($arguments, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
```
@endif
@if(!empty($options))

## Options

```json
{!! json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
```
@endif

@include('telescope::markdown.partials.related')
