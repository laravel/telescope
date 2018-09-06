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
    <preview-screen title="Notification Details" resource="notifications" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Channel</td>
                <td>
                    {{slotProps.entry.content.channel}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Notification</td>
                <td>
                    {{slotProps.entry.content.notification}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Notifiable</td>
                <td>
                    {{slotProps.entry.content.notifiable}}
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
    </preview-screen>
</template>
