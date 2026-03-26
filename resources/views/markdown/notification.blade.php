# Notification Details

- **Notification:** {!! $notification !!}
@if(!empty($queued))
- **Queued:** Yes
@endif
- **Channel:** {!! $channel !!}
- **Notifiable:** {!! $notifiable !!}
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
