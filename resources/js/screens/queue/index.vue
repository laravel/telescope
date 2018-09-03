<script type="text/ecmascript-6">
    export default {}
</script>

<template>
    <index-screen title="Queue" resource="queue">
        <tr slot="table-header">
            <th scope="col">Job</th>
            <th scope="col">Queue</th>
            <th scope="col">Connection</th>
            <th scope="col">Status</th>
            <th scope="col">Since</th>
            <th scope="col"></th>
        </tr>


        <tr slot="row" slot-scope="slotProps">
            <td>{{truncate(slotProps.entry.content.name, 20)}}</td>
            <td class="table-fit">{{truncate(slotProps.entry.content.queue, 20)}}</td>
            <td class="table-fit">{{truncate(slotProps.entry.content.connection, 20)}}</td>
            <td class="table-fit">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-danger" v-if="slotProps.entry.content.status == 'failed'">
                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z"/>
                </svg>

                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-success" v-else>
                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z"/>
                </svg>
            </td>
            <td class="table-fit">{{timeAgo(slotProps.entry.created_at)}}</td>
            <td class="table-fit">
                <router-link :to="{name:'queue-preview', params:{id: slotProps.entry.id}}" class="control-action">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                        <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                    </svg>
                </router-link>
            </td>
        </tr>
    </index-screen>
</template>
