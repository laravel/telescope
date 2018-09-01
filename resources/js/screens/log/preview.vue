<script type="text/ecmascript-6">
    export default {
        components: {
            'code-preview': require('./../../components/ExceptionCodePreview'),
            'stack-trace': require('./../../components/Stacktrace')
        }
    }
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
            <code-preview
                    v-if="slotProps.entry.content.exception"
                    :lines="slotProps.entry.content.exception.line_preview"
                    :highlighted-line="slotProps.entry.content.exception.line">
            </code-preview>

            <pre class="bg-dark p-4 mb-0 text-white" v-if="!slotProps.entry.content.exception">{{slotProps.entry.content.message}}</pre>
        </div>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <div class="card" v-if="slotProps.entry.content.exception && slotProps.entry.content.exception.trace.length">
                <div class="card-header"><h5>Stacktrace</h5></div>

                <stack-trace :trace="slotProps.entry.content.exception.trace"></stack-trace>
            </div>
        </div>
    </preview-screen>
</template>

<style scoped>

</style>