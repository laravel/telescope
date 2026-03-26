# Scheduled Task Details

- **Command:** {!! $command !!}
@if(!empty($description))
- **Description:** {!! $description !!}
@endif
- **Expression:** {!! $expression !!}
@if(!empty($timezone))
- **Timezone:** {!! $timezone !!}
@endif
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
@if(!empty($output))

## Output

```
{!! $output !!}
```
@endif
