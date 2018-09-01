<script type="text/ecmascript-6">
    export default {}
</script>

<template>
    <preview-screen title="Notification Preview" resource="notifications" :id="$route.params.id">
        <tbody slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">Time</td>
            <td>
                {{localTime(slotProps.entry.created_at)}} ({{timeAgo(slotProps.entry.created_at, false)}})
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Channel</td>
            <td>
                {{slotProps.entry.content.channel}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Notification</td>
            <td>
                {{slotProps.entry.content.notification}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Notifiable</td>
            <td>
                {{slotProps.entry.content.notifiable}}
            </td>
        </tr>
        </tbody>
    </preview-screen>
</template>