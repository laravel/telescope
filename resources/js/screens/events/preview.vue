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
    <preview-screen title="Event Preview" resource="events" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Event</td>
                <td>
                    {{slotProps.entry.content.event_name}}
                </td>
            </tr>

            <tr v-if="job">
                <td class="table-fit font-weight-bold">Job</td>
                <td>
                    <router-link :to="{name:'queue-preview', params:{id: job.id}}" class="control-action">
                        Preview Job
                    </router-link>
                </td>
            </tr>

            <tr v-if="request">
                <td class="table-fit font-weight-bold">Request</td>
                <td>
                    <router-link :to="{name:'request-preview', params:{id: request.id}}" class="control-action">
                        Preview Request
                    </router-link>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.event_payload.length">
                <div class="card-header"><h5>Event Data</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.event_payload}}</pre>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.listeners && slotProps.entry.content.listeners.length">
                <div class="card-header"><h5>Registered Listeners</h5></div>

                <table class="table mb-0">
                    <tbody>
                    <tr v-for="listener in slotProps.entry.content.listeners">
                        <td class="bg-secondary">{{listener}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </preview-screen>
</template>