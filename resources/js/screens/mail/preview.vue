<script type="text/ecmascript-6">
import _ from 'lodash';

export default {
    methods: {
        /**
         * Format the given list of addresses.
         */
        formatAddresses(addresses){
            return _.chain(addresses).map((name, email) => {
                return (name ? "<" + name + "> " : '') + email;
            }).join(', ').value()
        },

        /**
         * Format bytes into a human-readable string.
         */
        formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Get the download URL for an attachment.
         */
        attachmentUrl(index) {
            return Telescope.basePath + '/telescope-api/mail/' + this.$route.params.id + '/attachments/' + index;
        }
    },
}
</script>

<template>
    <preview-screen title="Mail Details" resource="mail" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Mailable</td>
                <td>
                    {{ slotProps.entry.content.mailable }}

                    <span class="badge badge-secondary ml-2" v-if="slotProps.entry.content.queued"> Queued </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">From</td>
                <td>
                    {{ formatAddresses(slotProps.entry.content.from) }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">To</td>
                <td>
                    {{ formatAddresses(slotProps.entry.content.to) }}
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.replyTo">
                <td class="table-fit text-muted">Reply-To</td>
                <td>
                    {{ formatAddresses(slotProps.entry.content.replyTo) }}
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.cc">
                <td class="table-fit text-muted">CC</td>
                <td>
                    {{ formatAddresses(slotProps.entry.content.cc) }}
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.bcc">
                <td class="table-fit text-muted">BCC</td>
                <td>
                    {{ formatAddresses(slotProps.entry.content.bcc) }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Subject</td>
                <td>
                    {{ slotProps.entry.content.subject }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Download</td>
                <td>
                    <a :href="Telescope.basePath + '/telescope-api/mail/' + $route.params.id + '/download'"
                        >Download .eml file</a
                    >
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.attachments && slotProps.entry.content.attachments.length">
                <td class="table-fit text-muted">Attachments</td>
                <td>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(attachment, index) in slotProps.entry.content.attachments" :key="index">
                                <td><a :href="attachmentUrl(index)">{{ attachment.filename }}</a></td>
                                <td>{{ formatBytes(attachment.size) }}</td>
                                <td>{{ attachment.mime_type }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <div class="card">
                <iframe
                    :src="Telescope.basePath + '/telescope-api/mail/' + $route.params.id + '/preview'"
                    width="100%"
                    height="400"
                ></iframe>
            </div>
        </div>
    </preview-screen>
</template>

<style scoped>
iframe {
    border: none;
}
</style>
