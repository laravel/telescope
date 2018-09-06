<script type="text/ecmascript-6">
    export default {
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
    <preview-screen title="Cache Details" resource="cache" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Action</td>
                <td>
                    {{slotProps.entry.content.type}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Key</td>
                <td>
                    {{slotProps.entry.content.key}}
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.expiration">
                <td class="table-fit font-weight-bold">Expiration</td>
                <td>
                    {{slotProps.entry.content.expiration}}
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

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.value">
                <div class="card-header"><h5>Value</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.value}}</pre>
            </div>
        </div>
    </preview-screen>
</template>
