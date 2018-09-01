<script type="text/ecmascript-6">
    export default {}
</script>

<template>
    <preview-screen title="Request Preview" resource="requests" :id="$route.params.id">
        <tbody slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">Time</td>
            <td>
                {{localTime(slotProps.entry.created_at)}} ({{timeAgo(slotProps.entry.created_at, false)}})
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Path</td>
            <td>
                {{slotProps.entry.content.uri}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Method</td>
            <td>
                {{slotProps.entry.content.method}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Status</td>
            <td>
                {{slotProps.entry.content.response_status}}
            </td>
        </tr>
        </tbody>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.payload">
                <div class="card-header"><h5>Payload</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.payload}}</pre>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.headers">
                <div class="card-header"><h5>Headers</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.headers}}</pre>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.response">
                <div class="card-header"><h5>Response</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.response}}</pre>
            </div>
        </div>
    </preview-screen>
</template>