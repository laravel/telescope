# Client Request Details

- **Method:** {!! $method !!}
- **URL:** {!! $uri !!}
- **Status:** {!! $response_status !!}
- **Duration:** {!! $duration ?? 'N/A' !!}ms
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
@if(!empty($payload))

## Payload

```json
{!! json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
```
@endif
@if(!empty($response))

## Response

```json
{!! is_string($response) ? $response : json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
```
@endif
@if(!empty($headers))

## Headers

@foreach($headers as $key => $value)
* **{!! $key !!}**: {!! is_array($value) ? implode(', ', $value) : $value !!}
@endforeach
@endif
@if(!empty($response_headers))

## Response Headers

@foreach($response_headers as $key => $value)
* **{!! $key !!}**: {!! is_array($value) ? implode(', ', $value) : $value !!}
@endforeach
@endif
