<script type="text/ecmascript-6">
    export default {
        components: {
            'code-preview': require('./../../components/ExceptionCodePreview'),
            'stack-trace': require('./../../components/Stacktrace')
        },

        data(){
            return {
                entry: null,
                batch: [],
            };
        },
    }
</script>

<template>
    <preview-screen title="Exception Details" resource="exceptions" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Type</td>
                <td>
                    {{slotProps.entry.content.class}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Location</td>
                <td>
                    {{slotProps.entry.content.file}}:{{slotProps.entry.content.line}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <div class="card">
                <div class="card-header">
                    <h5>Exception Message</h5>
                </div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.message}}</pre>
            </div>

            <div class="card mt-5">
                <div class="card-header">
                    <h5>Location</h5>
                </div>

                <code-preview
                        :lines="slotProps.entry.content.line_preview"
                        :highlighted-line="slotProps.entry.content.line">
                </code-preview>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.trace.length">
                <div class="card-header"><h5>Stacktrace</h5></div>
                <stack-trace :trace="slotProps.entry.content.trace"></stack-trace>
            </div>
        </div>
    </preview-screen>
</template>

<style scoped>

</style>
