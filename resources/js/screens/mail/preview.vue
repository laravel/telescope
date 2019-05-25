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
    }
</script>

<template>
    <preview-screen title="Mail Details" resource="mail" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Mailable</td>
                <td>
                    {{slotProps.entry.content.mailable}}

                    <span class="badge badge-secondary font-weight-light ml-2" v-if="slotProps.entry.content.queued">
                        Queued
                    </span>
                </td>
            </tr>

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
                    <a :href="Telescope.basePath + '/telescope-api/mail/'+$route.params.id+'/download'">Download .eml file</a>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <iframe :src="Telescope.basePath + '/telescope-api/mail/'+$route.params.id+'/preview'" width="100%" height="400"></iframe>
        </div>
    </preview-screen>
</template>

<style scoped>
    iframe {
        border: none;
    }
</style>
