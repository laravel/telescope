<script type="text/ecmascript-6">
    export default {
        methods: {
            /**
             * Format the given list of addresses.
             */
            formatAddresses(addresses){
                return _.chain(addresses).map((name, email) => {
                    return (name ? "<" + name + "> " : '') + email;
                }).join(', ').value()
            }
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
    <preview-screen title="Mail Preview" resource="mail" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">From</td>
                <td>
                    {{formatAddresses(slotProps.entry.content.from)}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">To</td>
                <td>
                    {{formatAddresses(slotProps.entry.content.to)}}
                </td>
            </tr>

            <tr v-if="slotProps.entry.replyTo">
                <td class="table-fit font-weight-bold">Reply-To</td>
                <td>
                    {{formatAddresses(slotProps.entry.content.replyTo)}}
                </td>
            </tr>

            <tr v-if="slotProps.entry.cc">
                <td class="table-fit font-weight-bold">CC</td>
                <td>
                    {{formatAddresses(slotProps.entry.content.cc)}}
                </td>
            </tr>

            <tr v-if="slotProps.entry.bcc">
                <td class="table-fit font-weight-bold">BCC</td>
                <td>
                    {{formatAddresses(slotProps.entry.content.bcc)}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Subject</td>
                <td>
                    {{slotProps.entry.content.subject}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Download</td>
                <td>
                    <a :href="'/telescope/telescope-api/mail/'+$route.params.id+'/download'">Download .eml file</a>
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

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <iframe :src="'/telescope/telescope-api/mail/'+$route.params.id+'/preview'" width="100%" height="400"></iframe>
        </div>
    </preview-screen>
</template>

<style scoped>
    iframe {
        border: none;
    }
</style>