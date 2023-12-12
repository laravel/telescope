<script type="text/ecmascript-6">
    export default {
        data(){
            return {
                entry: null,
                batch: [],
            };
        },
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

                    <span class="badge badge-secondary font-weight-light ml-2" v-if="slotProps.entry.content.queued">
                        Queued
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Notifiable</td>
                <td>
                    {{slotProps.entry.content.notifiable}}
                </td>
            </tr>
        </template>
    </preview-screen>
</template>
