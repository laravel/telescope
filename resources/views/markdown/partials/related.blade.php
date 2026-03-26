@php
$queries = $batch->where('type', 'query');
$exceptions = $batch->where('type', 'exception');
$logs = $batch->where('type', 'log');
$events = $batch->where('type', 'event');
$models = $batch->where('type', 'model');
@endphp
@if($queries->isNotEmpty())

## Queries ({!! $queries->count() !!})

@foreach($queries as $query)
* `{!! $query->content['sql'] !!}` ({!! $query->content['time'] !!}ms)
@endforeach
@endif
@if($exceptions->isNotEmpty())

## Exceptions

@foreach($exceptions as $exception)
* **{!! $exception->content['class'] !!}**: {!! $exception->content['message'] !!} at {!! $exception->content['file'] !!}:{!! $exception->content['line'] !!}
@endforeach
@endif
@if($logs->isNotEmpty())

## Logs

@foreach($logs as $log)
* [{!! strtoupper($log->content['level']) !!}] {!! $log->content['message'] !!}
@endforeach
@endif
@if($models->isNotEmpty())

## Models

@foreach($models as $model)
* {!! $model->content['action'] !!} {!! $model->content['model'] !!}
@endforeach
@endif
@if($events->isNotEmpty())

## Events

@foreach($events as $event)
* {!! $event->content['name'] !!}
@endforeach
@endif
