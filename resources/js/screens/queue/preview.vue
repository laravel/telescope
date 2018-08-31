<script type="text/ecmascript-6">
    export default {}
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
                {{slotProps.entry.content.tries}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Timeout</td>
            <td>
                {{slotProps.entry.content.timeout}}
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
            <pre class="bg-dark px-4 mb-0 text-white" v-if="slotProps.entry.content.exception">
                <p v-for="(content, number) in slotProps.entry.content.exception.line_preview"
                   class="mb-0"
                   :class="{'text-danger': number == slotProps.entry.content.exception.line}"><span class="mr-4">{{number}}</span> <span>{{content}}</span></p>
            </pre>

            <pre class="bg-dark p-4 mb-0 text-white" v-if="!slotProps.entry.content.exception">{{slotProps.entry.content.message}}</pre>
        </div>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <div class="card" v-if="slotProps.entry.content.exception && slotProps.entry.content.exception.trace.length">
                <div class="card-header"><h5>Stacktrace</h5></div>
                <table class="table mb-0">
                    <tbody>
                    <tr v-for="line in slotProps.entry.content.exception.trace">
                        <td class="bg-secondary">{{line.file}}:{{line.line}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </preview-screen>
</template>

<style scoped>

</style>