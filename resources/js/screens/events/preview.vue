<script type="text/ecmascript-6">
    export default {}
</script>

<template>
    <preview-screen title="Event Preview" resource="events" :id="$route.params.id">
        <tbody slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">Time</td>
            <td>
                {{localTime(slotProps.entry.created_at)}} ({{timeAgo(slotProps.entry.created_at, false)}})
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Event</td>
            <td>
                {{slotProps.entry.content.event_name}}
            </td>
        </tr>
        </tbody>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.event_payload.length">
                <div class="card-header"><h5>Event Data</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.event_payload}}</pre>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.listeners && slotProps.entry.content.listeners.length">
                <div class="card-header"><h5>Registered Listeners</h5></div>

                <table class="table mb-0">
                    <tbody>
                    <tr v-for="listener in slotProps.entry.content.listeners">
                        <td class="bg-secondary">{{listener}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </preview-screen>
</template>