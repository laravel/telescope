# Job Details

- **Job:** {!! $name !!}
- **Status:** {!! $status !!}
- **Connection:** {!! $connection !!}
- **Queue:** {!! $queue !!}
- **Tries:** {!! $tries !!}
@if(isset($timeout))
- **Timeout:** {!! $timeout !!}s
@endif
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
@if(!empty($data))

## Data

```json
{!! json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
```
@endif
@if(!empty($exception))

## Exception

- **{!! $exception['class'] ?? 'Exception' !!}:** {!! $exception['message'] ?? '' !!}

### Stack Trace

```
@foreach($exception['trace'] ?? [] as $i => $frame)
{!! $i !!} - {!! $frame['file'] !!}:{!! $frame['line'] !!}
@endforeach
```
@endif

@include('telescope::markdown.partials.related')
