@if(isset($user) && is_array($user))
@if(isset($user['id']))
- **User ID:** {!! $user['id'] !!}
@endif
@if(isset($user['name']))
- **User Name:** {!! $user['name'] !!}
@endif
@if(isset($user['email']))
- **User Email:** {!! $user['email'] !!}
@endif
@endif
