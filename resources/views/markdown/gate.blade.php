# Gate Details

- **Ability:** {!! $ability !!}
- **Result:** {!! ucfirst($result) !!}
@if(!empty($message))
- **Message:** {!! $message !!}
@endif
@if(isset($file))
- **Location:** {!! $file !!}:{!! $line !!}
@endif
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
