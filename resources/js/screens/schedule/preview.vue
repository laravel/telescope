<script type="text/ecmascript-6">
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
                <td class="table-fit font-weight-bold">Description</td>
                <td>
                    {{slotProps.entry.content.description || '-'}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Command</td>
                <td>
                    {{slotProps.entry.content.command || '-'}}
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

        <div slot="after-attributes-card" slot-scope="slotProps" v-if="slotProps.entry.content.output">
            <div class="card mt-5">
                <div class="card-header"><h5>Output</h5></div>

                <pre class="code-bg p-4 mb-0 text-white">{{ slotProps.entry.content.output }}</pre>
            </div>
        </div>
    </preview-screen>
</template>
