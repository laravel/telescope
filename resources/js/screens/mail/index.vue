<script type="text/ecmascript-6">
    export default {
        methods: {
            recipientsCount(entry){
                return _.union(Object.keys(entry.content.to),
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
            <th scope="col">Recipients</th>
            <th scope="col">Happened</th>
            <th scope="col"></th>
        </tr>


        <template slot="row" slot-scope="slotProps">
            <td>
                <span :title="slotProps.entry.content.mailable">{{truncate(slotProps.entry.content.mailable || '-', 70)}}</span>

                <span class="badge badge-secondary font-weight-light ml-2" v-if="slotProps.entry.content.queued">
                    Queued
                </span>

                <br>

                <small class="text-muted" :title="slotProps.entry.content.subject">
                    Subject: {{truncate(slotProps.entry.content.subject, 90)}}
                </small>
            </td>

            <td class="table-fit">{{recipientsCount(slotProps.entry)}}</td>

            <td class="table-fit" :data-timeago="slotProps.entry.created_at">{{timeAgo(slotProps.entry.created_at)}}</td>

            <td class="table-fit">
                <router-link :to="{name:'mail-preview', params:{id: slotProps.entry.id}}" class="control-action">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                        <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                    </svg>
                </router-link>
            </td>
        </template>
    </index-screen>
</template>
