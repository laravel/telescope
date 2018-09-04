<script type="text/ecmascript-6">
    export default {
        components: {
            'code-preview': require('./../../components/ExceptionCodePreview'),
            'stack-trace': require('./../../components/Stacktrace')
        }
    }
</script>

<template>
    <preview-screen title="Job Preview" resource="queue" :id="$route.params.id">
        <tbody slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">Time</td>
            <td>
                {{localTime(slotProps.entry.created_at)}} ({{timeAgo(slotProps.entry.created_at, false)}})
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Status</td>
            <td>
                {{slotProps.entry.content.status}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Job</td>
            <td>
                {{slotProps.entry.content.name}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Tries</td>
            <td>
                {{slotProps.entry.content.tries || '-'}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Timeout</td>
            <td>
                {{slotProps.entry.content.timeout || '-'}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Queue</td>
            <td>
                {{slotProps.entry.content.queue}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Connection</td>
            <td>
                {{slotProps.entry.content.connection}}
            </td>
        </tr>

        <tr v-if="slotProps.entry.content.status == 'failed'">
            <td class="table-fit font-weight-bold">Exception</td>
            <td>
                {{slotProps.entry.content.exception.message}}
            </td>
        </tr>

        </tbody>

        <div slot="below-table" slot-scope="slotProps">
            <code-preview
                    v-if="slotProps.entry.content.exception"
                    :lines="slotProps.entry.content.exception.line_preview"
                    :highlighted-line="slotProps.entry.content.exception.line">
            </code-preview>
        </div>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.exception && slotProps.entry.content.exception.trace.length">
                <div class="card-header"><h5>Stacktrace</h5></div>

                <stack-trace :trace="slotProps.entry.content.exception.trace"></stack-trace>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.data">
                <div class="card-header"><h5>Job Data</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.data}}</pre>
            </div>
        </div>
    </preview-screen>
</template>

<style scoped>

</style>