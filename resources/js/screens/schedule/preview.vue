<script type="text/ecmascript-6">
    import axios from 'axios';

    export default {
        data(){
            return {
                entry: null,
                batch: [],
            };
        }
    }
</script>

<template>
    <preview-screen title="Scheduled Command Details" resource="requests" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Command</td>
                <td>
                    {{slotProps.entry.content.description || '-'}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Expression</td>
                <td>
                    {{slotProps.entry.content.expression}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">User</td>
                <td>
                    {{slotProps.entry.content.user || '-'}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Timezone</td>
                <td>
                    {{slotProps.entry.content.timezone || '-'}}
                </td>
            </tr>
        </template>
    </preview-screen>
</template>
