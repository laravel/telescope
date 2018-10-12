<script type="text/ecmascript-6">
    export default {}
</script>

<template>
    <index-screen title="Exceptions" resource="exceptions">
        <tr slot="table-header">
            <th scope="col" v-if="!$route.query.family_hash">Type</th>
            <th scope="col" v-if="!$route.query.family_hash && !$route.query.tag">Occurrences</th>
            <th scope="col" v-if="$route.query.family_hash">Message</th>
            <th scope="col">Happened</th>
            <th scope="col"></th>
        </tr>

        <template slot="row" slot-scope="slotProps">
            <td :title="slotProps.entry.content.class" v-if="!$route.query.family_hash">
                {{truncate(slotProps.entry.content.class, 70)}}<br>

                <small class="text-muted">{{truncate(slotProps.entry.content.message, 100)}}</small>
            </td>

            <td class="table-fit" v-if="!$route.query.family_hash && !$route.query.tag">
                <span>{{slotProps.entry.content.occurrences}}</span>
            </td>

            <td :title="slotProps.entry.content.message" v-if="$route.query.family_hash">
                {{truncate(slotProps.entry.content.message, 80)}}<br>

                <small class="text-muted">
                    <span v-if="slotProps.entry.content.user && slotProps.entry.content.user.email">
                        User: {{ slotProps.entry.content.user.email }} ({{ slotProps.entry.content.user.id }})
                    </span>

                    <span v-else>
                        User: N/A
                    </span>
                </small>
            </td>

            <td class="table-fit" :data-timeago="slotProps.entry.created_at">{{timeAgo(slotProps.entry.created_at)}}</td>

            <td class="table-fit">
                <router-link :to="{name:'exception-preview', params:{id: slotProps.entry.id}}" class="control-action">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                        <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                    </svg>
                </router-link>
            </td>
        </template>
    </index-screen>
</template>
