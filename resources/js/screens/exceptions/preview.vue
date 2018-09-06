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

        computed: {
            job(){
                return _.find(this.batch, {type: 4})
            },

            request(){
                return _.find(this.batch, {type: 8})
            }
        }
    }
</script>

<template>
    <preview-screen title="Exception Details" resource="log" :id="$route.params.id">
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

            <tr>
                <td class="table-fit font-weight-bold">Message</td>
                <td>
                    {{slotProps.entry.content.message}}
                </td>
            </tr>

            <tr v-if="job">
                <td class="table-fit font-weight-bold">Job</td>
                <td>
                    <router-link :to="{name:'queue-preview', params:{id: job.id}}" class="control-action">
                        View Job
                    </router-link>
                </td>
            </tr>

            <tr v-if="request">
                <td class="table-fit font-weight-bold">Request</td>
                <td>
                    <router-link :to="{name:'request-preview', params:{id: request.id}}" class="control-action">
                        View Request
                    </router-link>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <div class="card">
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
