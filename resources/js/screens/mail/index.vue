<script type="text/ecmascript-6">
import _ from 'lodash';

export default {
    methods: {
        recipientsCount(entry){
            return _.union((entry.content.to ? Object.keys(entry.content.to) : []),
                    (entry.content.cc ? Object.keys(entry.content.cc) : []),
                    (entry.content.bcc ? Object.keys(entry.content.bcc) : []),
                    (entry.content.replyTo ? Object.keys(entry.content.replyTo) : [])).length;
        }
    }
}
</script>

<template>
    <index-screen title="Mail" resource="mail">
        <tr slot="table-header">
            <th scope="col">Mailable</th>
            <th scope="col" class="text-right">Recipients</th>
            <th scope="col">Happened</th>
            <th scope="col"></th>
        </tr>

        <template slot="row" slot-scope="slotProps">
            <td>
                <span :title="slotProps.entry.content.mailable || slotProps.entry.content.subject">{{
                    truncate(slotProps.entry.content.mailable || slotProps.entry.content.subject || '-', 70)
                }}</span>

                <span class="badge badge-secondary ml-2" v-if="slotProps.entry.content.queued"> Queued </span>

                <span class="badge badge-secondary ml-2" v-if="slotProps.entry.content.attachments && slotProps.entry.content.attachments.length">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="12" height="12" fill="currentColor" style="vertical-align: -1px;">
                        <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z" clip-rule="evenodd" />
                    </svg>
                    {{ slotProps.entry.content.attachments.length }}
                </span>

                <br />

                <small class="text-muted" :title="slotProps.entry.content.subject">
                    Subject: {{ truncate(slotProps.entry.content.subject, 90) }}
                </small>
            </td>

            <td class="table-fit text-right text-muted">
                {{ recipientsCount(slotProps.entry) }}
            </td>

            <td
                class="table-fit text-muted"
                :data-timeago="slotProps.entry.created_at"
                :title="slotProps.entry.created_at"
            >
                {{ timeAgo(slotProps.entry.created_at) }}
            </td>

            <td class="table-fit">
                <router-link
                    :to="{
                        name: 'mail-preview',
                        params: { id: slotProps.entry.id },
                    }"
                    class="control-action"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </router-link>
            </td>
        </template>
    </index-screen>
</template>
