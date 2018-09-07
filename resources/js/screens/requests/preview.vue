<script type="text/ecmascript-6">
    import axios from 'axios';

    export default {
        data() {
            return {
                entry: null,
                batch: [],
            };
        }
    }
</script>

<template>
    <preview-screen title="Request Details" resource="requests" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">Path</td>
            <td>
                {{slotProps.entry.content.uri}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Method</td>
            <td>
                {{slotProps.entry.content.method}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Status</td>
            <td>
                {{slotProps.entry.content.response_status}}
            </td>
        </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.payload">
                <div class="card-header"><h5>Payload</h5></div>
                <div class="bg-dark p-4 mb-0 text-white">
                    <tree-view :data="slotProps.entry.content.payload" :options="{maxDepth: 3}"></tree-view>
                </div>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.headers">
                <div class="card-header"><h5>Headers</h5></div>

                <div class="bg-dark p-4 mb-0 text-white">
                    <tree-view :data="slotProps.entry.content.headers" :options="{maxDepth: 3}"></tree-view>
                </div>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.response">
                <div class="card-header"><h5>Response</h5></div>

                <div class="bg-dark p-4 mb-0 text-white">
                    <tree-view :data="slotProps.entry.content.response" :options="{maxDepth: 3}"></tree-view>
                </div>
            </div>

            <!-- Additional Information -->
            <related-entries :entry="entry" :batch="batch">
            </related-entries>
        </div>
    </preview-screen>
</template>
