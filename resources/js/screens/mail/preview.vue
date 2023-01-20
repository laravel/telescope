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
                <td class="table-fit text-muted">Mailable</td>
                <td>
                    {{slotProps.entry.content.mailable}}

                    <span class="badge badge-secondary ml-2" v-if="slotProps.entry.content.queued">
                        Queued
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">From</td>
                <td>
                    {{formatAddresses(slotProps.entry.content.from)}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">To</td>
                <td>
                    {{formatAddresses(slotProps.entry.content.to)}}
                </td>
            </tr>

            <tr v-if="slotProps.entry.replyTo">
                <td class="table-fit text-muted">Reply-To</td>
                <td>
                    {{formatAddresses(slotProps.entry.content.replyTo)}}
                </td>
            </tr>

            <tr v-if="slotProps.entry.cc">
                <td class="table-fit text-muted">CC</td>
                <td>
                    {{formatAddresses(slotProps.entry.content.cc)}}
                </td>
            </tr>

            <tr v-if="slotProps.entry.bcc">
                <td class="table-fit text-muted">BCC</td>
                <td>
                    {{formatAddresses(slotProps.entry.content.bcc)}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Subject</td>
                <td>
                    {{slotProps.entry.content.subject}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Download</td>
                <td>
                    <a :href="Telescope.basePath + '/telescope-api/mail/'+$route.params.id+'/download'">Download .eml file</a>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <div class="card">
              <iframe :src="Telescope.basePath + '/telescope-api/mail/'+$route.params.id+'/preview'" width="100%" height="400"></iframe>
          </div>
        </div>
    </preview-screen>
</template>

<style scoped>
    iframe {
        border: none;
    }
</style>
