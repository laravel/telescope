# Query Details

@if(!empty($slow))
- **Slow:** Yes
@endif
- **Connection:** {!! $connection !!}
- **Duration:** {!! $time !!}ms
@if(isset($file))
- **Location:** {!! $file !!}:{!! $line !!}
@endif
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')

## SQL

```sql
{!! $sql !!}
```
