# Batch Details

- **Name:** {!! $name ?? $id ?? '' !!}
- **Status:** {!! $failedJobs > 0 ? 'Failures' : ($pendingJobs > 0 ? 'Pending' : 'Finished') !!}
- **Total Jobs:** {!! $totalJobs !!}
- **Pending Jobs:** {!! $pendingJobs !!}
- **Failed Jobs:** {!! $failedJobs !!}
- **Progress:** {!! $progress !!}%
- **Connection:** {!! $connection !!}
- **Queue:** {!! $queue !!}
@if(!empty($cancelledAt))
- **Cancelled At:** {!! $cancelledAt !!}
@endif
@if(!empty($finishedAt))
- **Finished At:** {!! $finishedAt !!}
@endif
- **Time:** {!! $createdAt->format('Y-m-d H:i:s') !!}
@if(isset($hostname))
- **Hostname:** {!! $hostname !!}
@endif
@include('telescope::markdown.partials.user')
@include('telescope::markdown.partials.tags')
