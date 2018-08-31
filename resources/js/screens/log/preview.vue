<script type="text/ecmascript-6">
    export default {}
</script>

<template>
    <preview-screen title="Log Preview" resource="log" :id="$route.params.id">
        <tbody slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">Time</td>
            <td>
                {{localTime(slotProps.entry.created_at)}} ({{timeAgo(slotProps.entry.created_at, false)}})
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Level</td>
            <td>
                {{slotProps.entry.content.level}}
            </td>
        </tr>

        <tr v-if="slotProps.entry.content.exception">
            <td class="table-fit font-weight-bold">Location</td>
            <td>
                {{slotProps.entry.content.exception.file}}:{{slotProps.entry.content.exception.line}}
            </td>
        </tr>

        <tr v-if="slotProps.entry.content.exception">
            <td class="table-fit font-weight-bold">Message</td>
            <td>
                {{slotProps.entry.content.message}}
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