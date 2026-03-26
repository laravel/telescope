# Mail Details

- **Mailable:** {!! $mailable ?? 'Mail' !!}
@if(!empty($queued))
- **Queued:** Yes
@endif
- **Subject:** {!! $subject ?? '' !!}
@if(!empty($from))
- **From:** @foreach($from as $address => $name){!! $name ? "$name <$address>" : $address !!}@if(!$loop->last), @endif @endforeach

@endif
@if(!empty($to))
- **To:** @foreach($to as $address => $name){!! $name ? "$name <$address>" : $address !!}@if(!$loop->last), @endif @endforeach

@endif
@if(!empty($cc))
- **CC:** @foreach($cc as $address => $name){!! $name ? "$name <$address>" : $address !!}@if(!$loop->last), @endif @endforeach

@endif
@if(!empty($bcc))
- **BCC:** @foreach($bcc as $address => $name){!! $name ? "$name <$address>" : $address !!}@if(!$loop->last), @endif @endforeach

@endif
@if(!empty($replyTo))
- **Reply To:** @foreach($replyTo as $address => $name){!! $name ? "$name <$address>" : $address !!}@if(!$loop->last), @endif @endforeach

@endif
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
