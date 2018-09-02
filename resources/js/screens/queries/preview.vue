<script type="text/ecmascript-6">
    export default {}
</script>

<template>
    <preview-screen title="Query Preview" resource="queries" :id="$route.params.id">
        <tbody slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">Time</td>
            <td>
                {{localTime(slotProps.entry.created_at)}} ({{timeAgo(slotProps.entry.created_at, false)}})
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Connection</td>
            <td>
                {{slotProps.entry.content.connection}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Duration</td>
            <td>
                {{slotProps.entry.content.time}}
            </td>
        </tr>
        </tbody>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <div class="card-header"><h5>Query & Bindings</h5></div>

                <code class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.sql}}</code>
                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.bindings}}</pre>
            </div>
        </div>
    </preview-screen>
</template>